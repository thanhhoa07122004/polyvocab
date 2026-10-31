<?php

namespace App\Services\Study;

use App\Models\Deck;
use App\Models\EntryMeaning;
use Illuminate\Support\Collection;

class QuestionGeneratorService
{
    /**
     * Tạo câu hỏi cho một nghĩa cụ thể theo dạng bài mong muốn.
     *
     * @param EntryMeaning $meaning
     * @param string $exerciseType
     * @param Deck $deck
     * @param Collection<int, EntryMeaning>|null $siblingMeanings Các nghĩa khác trong bộ để tạo phương án nhiễu
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateQuestion(
        EntryMeaning $meaning,
        string $exerciseType,
        Deck $deck,
        ?Collection $siblingMeanings = null,
        array $options = []
    ): array {
        $entry = $meaning->vocabularyEntry;
        $examples = $meaning->examples;
        $firstExample = $examples->first();

        // Chuẩn bị các nghĩa gây nhiễu nếu cần
        if (! $siblingMeanings) {
            $siblingMeanings = EntryMeaning::whereHas('vocabularyEntry', fn ($q) => $q->where('deck_id', $deck->id))
                ->where('id', '!=', $meaning->id)
                ->whereNotNull('meaning')
                ->where('meaning', '!=', '')
                ->inRandomOrder()
                ->take(5)
                ->get();
        }

        $matchingPairs = ($exerciseType === 'matching_pairs') ? $this->buildMatchingPairs($meaning, $siblingMeanings) : [];
        $derangedMatchingPairs = ($exerciseType === 'matching_pairs') ? $this->derangeMatchingPairs($matchingPairs) : ['terms' => [], 'meanings' => []];

        $base = [
            'id' => 'q_' . $meaning->id . '_' . uniqid(),
            'meaning_id' => $meaning->id,
            'exercise_type' => $exerciseType,
            'term' => $entry->term,
            'pronunciation' => $entry->pronunciation,
            'part_of_speech' => $meaning->part_of_speech,
            'meaning' => $meaning->meaning,
            'explanation' => $firstExample ? ($firstExample->sentence . ' (' . $firstExample->translation . ')') : $meaning->meaning,
        ];

        return match ($exerciseType) {
            'flashcard' => array_merge($base, [
                'type' => 'flashcard',
                'prompt' => 'Lật thẻ để kiểm tra khả năng nhớ nghĩa:',
                'front_text' => ($exerciseType === 'flashcard_meaning_to_term')
                    ? ($meaning->meaning ?: ($entry->note ?: 'Chưa có mô tả ý nghĩa'))
                    : $entry->term,
                'back_text' => ($exerciseType === 'flashcard_meaning_to_term')
                    ? $entry->term
                    : ($meaning->meaning ?: ($entry->note ?: 'Chưa có mô tả ý nghĩa')),
                'example_sentence' => $firstExample?->sentence,
                'example_translation' => $firstExample?->translation,
                'is_self_evaluated' => true,
            ]),

            'multiple_choice_meaning_to_term' => array_merge($base, [
                'type' => 'multiple_choice_meaning_to_term',
                'prompt' => 'Chọn từ vựng phù hợp với nghĩa sau:',
                'question_text' => $meaning->meaning,
                'options' => $this->buildTermOptions($meaning, $siblingMeanings),
                'correct_answer' => $entry->term,
                'is_self_evaluated' => false,
            ]),

            'fill_in_the_blank' => $this->buildFillInTheBlankQuestion($base, $meaning, $deck, $options),

            'type_the_word' => array_merge($base, [
                'type' => 'type_the_word',
                'prompt' => 'Nhìn nghĩa và gõ lại chính xác từ vựng:',
                'question_text' => $meaning->meaning,
                'correct_answer' => $entry->term,
                'is_self_evaluated' => false,
            ]),

            'listen_and_type' => array_merge($base, [
                'type' => 'listen_and_type',
                'prompt' => 'Nghe phát âm và gõ lại đúng từ vựng:',
                'audio_url' => $entry->audio_url,
                'correct_answer' => $entry->term,
                'is_self_evaluated' => false,
                'hide_term_text' => true,
            ]),

            'listen_and_choose' => array_merge($base, [
                'type' => 'listen_and_choose',
                'prompt' => 'Nghe phát âm và chọn nghĩa chính xác:',
                'audio_url' => $entry->audio_url,
                'options' => $this->buildMeaningOptions($meaning, $siblingMeanings),
                'correct_id' => $meaning->id,
                'correct_answer' => $meaning->meaning,
                'is_self_evaluated' => false,
                'hide_term_text' => true,
            ]),

            'matching_pairs' => array_merge($base, [
                'type' => 'matching_pairs',
                'prompt' => 'Ghép từng từ ở cột trái với nghĩa tương ứng ở cột phải:',
                'pairs' => $matchingPairs,
                'shuffled_terms' => $derangedMatchingPairs['terms'],
                'shuffled_meanings' => $derangedMatchingPairs['meanings'],
                'is_self_evaluated' => false,
            ]),

            'word_unscramble' => array_merge($base, [
                'type' => 'word_unscramble',
                'prompt' => 'Sắp xếp các mảnh chữ cái thành từ vựng đúng:',
                'question_text' => $meaning->meaning,
                'tiles' => $this->buildScrambledTiles($entry->term),
                'correct_answer' => $entry->term,
                'is_self_evaluated' => false,
            ]),

            default => array_merge($base, [
                'type' => 'multiple_choice_term_to_meaning',
                'prompt' => 'Chọn nghĩa đúng của từ vựng:',
                'question_text' => $entry->term,
                'options' => $this->buildMeaningOptions($meaning, $siblingMeanings),
                'correct_id' => $meaning->id,
                'correct_answer' => $meaning->meaning,
                'is_self_evaluated' => false,
            ]),
        };
    }

    /**
     * Loại bỏ các trường đáp án đúng trước khi gửi câu hỏi về client (trong bài kiểm tra).
     * Tuyệt đối không gửi đáp án đúng trong HTML/JSON trong chế độ bài kiểm tra chính thức.
     */
    public function stripAnswersForClient(array $question): array
    {
        $safe = $question;

        unset($safe['correct_answer']);
        unset($safe['correct_id']);
        unset($safe['explanation']);
        unset($safe['blank_answer']);
        unset($safe['acceptable_variants']);
        unset($safe['sentence_full']);

        if (isset($safe['type']) && $safe['type'] === 'fill_in_the_blank') {
            unset($safe['example_translation']);
            unset($safe['cloze_translation']);
        }

        if (! empty($safe['hide_term_text'])) {
            unset($safe['term']);
            unset($safe['pronunciation']);
            unset($safe['audio_term']);
        }

        if (isset($safe['type']) && in_array($safe['type'], ['listen_and_choose', 'multiple_choice_term_to_meaning'], true)) {
            unset($safe['meaning']);
        }

        if (isset($safe['options']) && is_array($safe['options'])) {
            foreach ($safe['options'] as $idx => $opt) {
                unset($safe['options'][$idx]['is_correct']);
            }
        }

        if (isset($safe['pairs']) && is_array($safe['pairs'])) {
            // Đối với ghép cặp trong bài kiểm tra, trả về danh sách từ và danh sách nghĩa đã xáo trộn riêng biệt, không cùng hàng
            $deranged = $this->derangeMatchingPairs($safe['pairs']);
            $terms = [];
            $meanings = [];
            foreach ($deranged['terms'] as $p) {
                $terms[] = ['id' => $p['id'], 'text' => $p['term']];
            }
            foreach ($deranged['meanings'] as $p) {
                $meanings[] = ['id' => $p['id'], 'text' => $p['meaning']];
            }
            $safe['unmatched_terms'] = $terms;
            $safe['unmatched_meanings'] = $meanings;
            unset($safe['pairs']);
        }

        return $safe;
    }

    /**
     * Chấm điểm một câu hỏi dựa trên đáp án của người dùng.
     *
     * @param array<string, mixed> $question
     * @param mixed $userAnswer
     * @return array{is_correct: bool, score: float, user_answer: mixed, correct_answer: mixed, feedback: string}
     */
    public function gradeAnswer(array $question, mixed $userAnswer): array
    {
        $type = $question['type'] ?? 'multiple_choice_term_to_meaning';
        $correctAnswer = $question['correct_answer'] ?? ($question['meaning'] ?? '');

        if ($userAnswer === null || $userAnswer === '') {
            return [
                'is_correct' => false,
                'is_empty' => true,
                'score' => 0.0,
                'user_answer' => null,
                'correct_answer' => $correctAnswer,
                'feedback' => 'Chưa trả lời câu hỏi này.',
            ];
        }

        if ($type === 'flashcard') {
            $isCorrect = in_array($userAnswer, ['remember', 'self_remember', 'pass'], true);
            return [
                'is_correct' => $isCorrect,
                'is_empty' => false,
                'score' => $isCorrect ? 1.0 : 0.0,
                'user_answer' => $isCorrect ? 'Đã nhớ' : 'Chưa nhớ',
                'correct_answer' => $correctAnswer,
                'feedback' => $isCorrect ? 'Tự đánh giá: Đã nhớ' : 'Tự đánh giá: Chưa nhớ',
            ];
        }

        if ($type === 'matching_pairs') {
            // $userAnswer là mảng các cặp: ['pair_id' => 'selected_meaning_id']
            $pairs = $question['pairs'] ?? [];
            $totalPairs = max(1, count($pairs));
            $matchedCount = 0;

            if (is_array($userAnswer)) {
                foreach ($pairs as $p) {
                    $pairId = (string) $p['id'];
                    if (isset($userAnswer[$pairId]) && (string) $userAnswer[$pairId] === $pairId) {
                        $matchedCount++;
                    }
                }
            }

            $score = round($matchedCount / $totalPairs, 2);
            $isAllCorrect = ($matchedCount === $totalPairs);

            return [
                'is_correct' => $isAllCorrect,
                'is_empty' => empty($userAnswer),
                'score' => $score,
                'user_answer' => "Ghép đúng {$matchedCount}/{$totalPairs} cặp",
                'correct_answer' => 'Ghép đúng toàn bộ các cặp',
                'feedback' => $isAllCorrect ? 'Xuất sắc! Bạn đã ghép đúng tất cả các cặp.' : "Bạn đã ghép đúng {$matchedCount}/{$totalPairs} cặp.",
            ];
        }

        if ($type === 'multiple_choice_term_to_meaning' || $type === 'listen_and_choose') {
            $correctId = (string) ($question['correct_id'] ?? $question['meaning_id']);
            $isCorrect = (string) $userAnswer === $correctId || ($this->normalizeText((string) $userAnswer) === $this->normalizeText((string) $correctAnswer));
            return [
                'is_correct' => $isCorrect,
                'is_empty' => false,
                'score' => $isCorrect ? 1.0 : 0.0,
                'user_answer' => (string) $userAnswer,
                'correct_answer' => $question['correct_answer'] ?? $question['meaning'],
                'feedback' => $isCorrect ? 'Chính xác! Lựa chọn hoàn toàn đúng.' : 'Chưa chính xác. Đáp án đúng là: ' . ($question['correct_answer'] ?? ''),
            ];
        }

        if ($type === 'multiple_choice_meaning_to_term') {
            $isCorrect = $this->normalizeText((string) $userAnswer) === $this->normalizeText((string) $correctAnswer);
            return [
                'is_correct' => $isCorrect,
                'is_empty' => false,
                'score' => $isCorrect ? 1.0 : 0.0,
                'user_answer' => (string) $userAnswer,
                'correct_answer' => $correctAnswer,
                'feedback' => $isCorrect ? 'Chính xác! Từ vựng hoàn toàn khớp.' : 'Chưa chính xác. Đáp án đúng là: ' . $correctAnswer,
            ];
        }

        if ($type === 'fill_in_the_blank') {
            $correctAnswer = $question['blank_answer'] ?? ($question['correct_answer'] ?? '');
            $acceptableVariants = (array) ($question['acceptable_variants'] ?? []);
            $clozeFormat = $question['cloze_format'] ?? 'input';

            $normalizedUser = $this->normalizeText((string) $userAnswer);
            $normalizedCorrect = $this->normalizeText((string) $correctAnswer);

            $isCorrect = false;
            if ($clozeFormat === 'choice') {
                $isCorrect = ($normalizedUser === $normalizedCorrect);
            } else {
                if ($normalizedUser === $normalizedCorrect) {
                    $isCorrect = true;
                } else {
                    foreach ($acceptableVariants as $variant) {
                        if ($this->normalizeText((string) $variant) === $normalizedUser) {
                            $isCorrect = true;
                            break;
                        }
                    }
                }
            }

            return [
                'is_correct' => $isCorrect,
                'is_empty' => false,
                'score' => $isCorrect ? 1.0 : 0.0,
                'user_answer' => (string) $userAnswer,
                'correct_answer' => $correctAnswer,
                'sentence_full' => $question['sentence_full'] ?? null,
                'example_translation' => $question['example_translation'] ?? null,
                'explanation' => $question['explanation'] ?? null,
                'feedback' => $isCorrect
                    ? 'Chính xác! Bạn đã điền đúng từ vào câu.'
                    : 'Chưa chính xác. Đáp án đúng là: ' . $correctAnswer,
            ];
        }

        // Dạng nhập text: type_the_word, listen_and_type, word_unscramble
        $normalizedUser = $this->normalizeText((string) $userAnswer);
        $normalizedCorrect = $this->normalizeText((string) $correctAnswer);
        $isCorrect = ($normalizedUser === $normalizedCorrect);

        return [
            'is_correct' => $isCorrect,
            'is_empty' => false,
            'score' => $isCorrect ? 1.0 : 0.0,
            'user_answer' => (string) $userAnswer,
            'correct_answer' => $correctAnswer,
            'feedback' => $isCorrect ? 'Chính xác!' : 'Chưa chính xác. Đáp án đúng là: ' . $correctAnswer,
        ];
    }

    /**
     * Chuẩn hóa văn bản để so sánh chính tả linh hoạt (Unicode NFC, xóa khoảng trắng thừa, dấu câu viền, đưa về chữ thường).
     */
    public function normalizeText(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }

        $trimmed = trim(mb_strtolower($text, 'UTF-8'));
        // Loại bỏ dấu câu viền
        return trim($trimmed, " .,!?;:'\"-—()[]{}");
    }

    /**
     * Tạo câu hỏi dạng Điền từ (Fill in the blank), hỗ trợ AI sinh câu hoặc câu ví dụ sẵn có.
     *
     * @param array<string, mixed> $base
     * @param EntryMeaning $meaning
     * @param Deck $deck
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function buildFillInTheBlankQuestion(
        array $base,
        EntryMeaning $meaning,
        Deck $deck,
        array $options = []
    ): array {
        $entry = $meaning->vocabularyEntry;
        $firstExample = $meaning->examples->first();
        $useAiCloze = (bool) ($options['use_ai_cloze'] ?? false);
        $aiService = app(\App\Services\Ai\AiSentenceService::class);

        // Sử dụng AI nếu người dùng bật hoặc khi từ vựng chưa có câu ví dụ nào trong bộ từ
        if (($useAiCloze || empty($firstExample?->sentence)) && $aiService->isConfigured()) {
            $aiCloze = $aiService->getOrCreateCloze($meaning, $deck->learningLanguage, $deck->meaningLanguage);

            if ($aiCloze) {
                $fullTranslation = $aiCloze->translation ?: $meaning->meaning;
                $clozeTranslation = $aiCloze->translation_with_blank ?: $this->buildClozeTranslation($fullTranslation, $meaning->meaning, $entry->term);

                // Xác định format: trắc nghiệm (choice) hoặc nhập từ (input) theo tỉ lệ cấu hình
                $choiceRatio = (float) config('polyvocab.ai_cloze.choice_ratio', 0.5);
                $isChoice = (mt_rand(1, 100) / 100) <= $choiceRatio;

                // Để tạo trắc nghiệm cần ít nhất 3 phương án nhiễu hợp lệ
                $distractors = is_array($aiCloze->distractors) ? $aiCloze->distractors : [];
                if ($isChoice && count($distractors) >= 3) {
                    $choiceOptions = [];
                    $choiceOptions[] = [
                        'id' => 'opt_correct',
                        'text' => $aiCloze->blank_answer,
                        'is_correct' => true,
                    ];

                    foreach (array_slice($distractors, 0, 3) as $dIdx => $dist) {
                        $choiceOptions[] = [
                            'id' => 'opt_distractor_' . ($dIdx + 1),
                            'text' => (string) $dist,
                            'is_correct' => false,
                        ];
                    }

                    shuffle($choiceOptions);

                    return array_merge($base, [
                        'type' => 'fill_in_the_blank',
                        'cloze_format' => 'choice',
                        'prompt' => 'Chọn từ thích hợp để điền vào chỗ trống trong câu:',
                        'cloze_sentence' => $aiCloze->sentence_with_blank,
                        'sentence_full' => $aiCloze->sentence_full,
                        'blank_answer' => $aiCloze->blank_answer,
                        'correct_answer' => $aiCloze->blank_answer,
                        'acceptable_variants' => $aiCloze->acceptable_variants ?? [],
                        'options' => $choiceOptions,
                        'cloze_translation' => $clozeTranslation,
                        'example_translation' => $fullTranslation,
                        'explanation' => $aiCloze->explanation,
                        'is_ai_generated' => true,
                        'is_self_evaluated' => false,
                    ]);
                }

                // Nếu không đủ điều kiện trắc nghiệm, chuyển sang kiểu nhập từ (input)
                return array_merge($base, [
                    'type' => 'fill_in_the_blank',
                    'cloze_format' => 'input',
                    'prompt' => 'Điền từ vựng thích hợp vào chỗ trống trong câu:',
                    'cloze_sentence' => $aiCloze->sentence_with_blank,
                    'sentence_full' => $aiCloze->sentence_full,
                    'blank_answer' => $aiCloze->blank_answer,
                    'correct_answer' => $aiCloze->blank_answer,
                    'acceptable_variants' => $aiCloze->acceptable_variants ?? [],
                    'cloze_translation' => $clozeTranslation,
                    'example_translation' => $fullTranslation,
                    'explanation' => $aiCloze->explanation,
                    'is_ai_generated' => true,
                    'is_self_evaluated' => false,
                ]);
            }
        }

        // Mặc định hoặc Fallback sang câu ví dụ có sẵn
        $fallbackSentence = $firstExample?->sentence
            ? $this->buildClozeSentence($entry->term, $firstExample->sentence)
            : '[ ______ ]';
        $fallbackFullTranslation = $firstExample?->translation ?: $meaning->meaning;
        $fallbackClozeTranslation = $firstExample?->translation
            ? $this->buildClozeTranslation($firstExample->translation, $meaning->meaning, $entry->term)
            : '[ ______ ]';

        return array_merge($base, [
            'type' => 'fill_in_the_blank',
            'cloze_format' => 'input',
            'prompt' => 'Điền từ vựng thích hợp vào chỗ trống trong câu:',
            'cloze_sentence' => $fallbackSentence,
            'sentence_full' => $firstExample?->sentence ?: $entry->term,
            'blank_answer' => $entry->term,
            'correct_answer' => $entry->term,
            'acceptable_variants' => [],
            'cloze_translation' => $fallbackClozeTranslation,
            'example_translation' => $fallbackFullTranslation,
            'explanation' => $firstExample ? ($firstExample->sentence . ' (' . $firstExample->translation . ')') : $meaning->meaning,
            'is_ai_generated' => false,
            'is_self_evaluated' => false,
        ]);
    }

    /**
     * Tạo câu dịch có chỗ trống [ ______ ] từ câu dịch đầy đủ và nét nghĩa / từ vựng.
     * Đảm bảo khi người học bấm Gợi ý, từ cần điền không bị lộ mà hiển thị dưới dạng khoảng trắng [ ______ ].
     */
    public function buildClozeTranslation(string $translation, string $meaningText, ?string $term = null): string
    {
        $blankMarker = '[ ______ ]';

        // 1. Nếu bản dịch đã có sẵn chỗ trống [ ______ ] hoặc ______ thì chuẩn hóa
        if (preg_match('/\[\s*_{3,}\s*\]|_{3,}/u', $translation)) {
            return trim(preg_replace('/\s+/', ' ', preg_replace('/\[\s*_{3,}\s*\]|_{3,}/u', $blankMarker, $translation)));
        }

        // 2. Tìm và thay thế các cụm từ nghĩa trong câu dịch
        $candidates = [];
        $cleanMeaning = preg_replace('/\([^)]*\)/u', ' ', $meaningText);
        $parts = preg_split('/[,;\/\n\r|]+/u', (string) $cleanMeaning);
        if ($parts) {
            foreach ($parts as $p) {
                $trimmed = trim($p, " \t\n\r\0\x0B-—:.,");
                if (mb_strlen($trimmed, 'UTF-8') >= 2) {
                    $candidates[] = $trimmed;
                }
            }
        }

        // Thêm cả cụm nghĩa gốc nếu chưa có
        $rawMeaningTrimmed = trim((string) preg_replace('/\([^)]*\)/u', ' ', $meaningText));
        if (mb_strlen($rawMeaningTrimmed, 'UTF-8') >= 2 && ! in_array($rawMeaningTrimmed, $candidates, true)) {
            $candidates[] = $rawMeaningTrimmed;
        }

        // Thêm term gốc (nếu bản dịch giữ nguyên từ gốc)
        if ($term && mb_strlen($term, 'UTF-8') >= 2) {
            $candidates[] = $term;
        }

        // Sắp xếp các cụm từ dài nhất lên trước để match ưu tiên cụm từ đầy đủ
        usort($candidates, fn ($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

        $replaced = false;
        $result = $translation;
        foreach ($candidates as $candidate) {
            $pattern = '/' . preg_quote($candidate, '/') . '/iu';
            if (preg_match($pattern, $result)) {
                $result = preg_replace($pattern, $blankMarker, $result, 1);
                $replaced = true;
                break;
            }
        }

        // Nếu các cụm từ đầy đủ không match, thử tách thành các từ đơn có ý nghĩa
        if (! $replaced) {
            $words = preg_split('/\s+/u', (string) $cleanMeaning);
            $meaningWords = [];
            if ($words) {
                foreach ($words as $w) {
                    $w = trim($w, " \t\n\r\0\x0B-—:.,");
                    if (mb_strlen($w, 'UTF-8') >= 2) {
                        $meaningWords[] = $w;
                    }
                }
            }
            usort($meaningWords, fn ($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));
            foreach ($meaningWords as $mw) {
                if (in_array(mb_strtolower($mw, 'UTF-8'), ['của', 'trong', 'cho', 'với', 'được', 'một', 'những', 'các', 'là', 'và', 'có'], true)) {
                    continue;
                }
                $pattern = '/' . preg_quote($mw, '/') . '/iu';
                if (preg_match($pattern, $result)) {
                    $result = preg_replace($pattern, $blankMarker, $result, 1);
                    $replaced = true;
                    break;
                }
            }
        }

        return trim(preg_replace('/\s+/', ' ', $result));
    }

    protected function buildClozeSentence(string $term, ?string $sentence): string
    {
        if (! $sentence) {
            return '[ ______ ]';
        }

        $pattern = '/' . preg_quote($term, '/') . '/iu';
        return preg_replace($pattern, '[ ______ ]', $sentence) ?: '[ ______ ]';
    }

    protected function buildMeaningOptions(EntryMeaning $meaning, Collection $distractors): array
    {
        $options = [];
        $options[] = [
            'id' => $meaning->id,
            'text' => $meaning->meaning,
            'is_correct' => true,
        ];

        foreach ($distractors->take(3) as $d) {
            $options[] = [
                'id' => $d->id,
                'text' => $d->meaning,
                'is_correct' => false,
            ];
        }

        shuffle($options);
        return $options;
    }

    protected function buildTermOptions(EntryMeaning $meaning, Collection $distractors): array
    {
        $entry = $meaning->vocabularyEntry;
        $options = [];
        $options[] = [
            'id' => $meaning->id,
            'text' => $entry->term,
            'is_correct' => true,
        ];

        foreach ($distractors->take(3) as $d) {
            $options[] = [
                'id' => $d->id,
                'text' => $d->vocabularyEntry->term ?? $d->meaning,
                'is_correct' => false,
            ];
        }

        shuffle($options);
        return $options;
    }

    protected function buildMatchingPairs(EntryMeaning $meaning, Collection $distractors): array
    {
        $pairs = [];
        $pairs[] = [
            'id' => $meaning->id,
            'term' => $meaning->vocabularyEntry->term,
            'meaning' => $meaning->meaning,
        ];

        foreach ($distractors->take(3) as $d) {
            $pairs[] = [
                'id' => $d->id,
                'term' => $d->vocabularyEntry->term,
                'meaning' => $d->meaning,
            ];
        }

        shuffle($pairs);
        return $pairs;
    }

    /**
     * Tạo 2 danh sách từ và nghĩa xáo trộn riêng biệt, bảo đảm các cặp cùng nghĩa KHÔNG nằm chung một hàng.
     *
     * @param array<int, array{id: int|string, term: string, meaning: string}> $pairs
     * @return array{terms: array<int, array{id: int|string, term: string, meaning: string}>, meanings: array<int, array{id: int|string, term: string, meaning: string}>}
     */
    public function derangeMatchingPairs(array $pairs): array
    {
        $terms = $pairs;
        shuffle($terms);

        $meanings = $pairs;
        shuffle($meanings);

        $n = count($pairs);
        if ($n > 1) {
            // Cố gắng xáo trộn ngẫu nhiên tối đa 30 lần để tìm hoán vị không có điểm cố định trùng hàng (derangement)
            $foundDerangement = false;
            for ($attempt = 0; $attempt < 30; $attempt++) {
                $hasSameRow = false;
                for ($i = 0; $i < $n; $i++) {
                    if ($terms[$i]['id'] === $meanings[$i]['id']) {
                        $hasSameRow = true;
                        break;
                    }
                }
                if (! $hasSameRow) {
                    $foundDerangement = true;
                    break;
                }
                shuffle($meanings);
            }

            // Nếu sau 30 lần vẫn còn phần tử trùng hàng, áp dụng dịch chuyển xoay vòng bảo đảm 100% không cùng hàng
            if (! $foundDerangement) {
                $meaningsById = [];
                foreach ($pairs as $p) {
                    $meaningsById[$p['id']] = $p;
                }
                $shiftedMeanings = [];
                for ($i = 0; $i < $n; $i++) {
                    $nextTermId = $terms[($i + 1) % $n]['id'];
                    $shiftedMeanings[] = $meaningsById[$nextTermId];
                }
                $meanings = $shiftedMeanings;
            }
        }

        return [
            'terms' => $terms,
            'meanings' => $meanings,
        ];
    }

    protected function buildScrambledTiles(string $term): array
    {
        $letters = mb_str_split($term, 1, 'UTF-8');
        $tiles = [];
        foreach ($letters as $idx => $char) {
            if ($char !== ' ') {
                $tiles[] = [
                    'id' => 'tile_' . $idx,
                    'letter' => $char,
                ];
            }
        }
        shuffle($tiles);
        return $tiles;
    }
}

