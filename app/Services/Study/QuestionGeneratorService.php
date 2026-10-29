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
     * @return array<string, mixed>
     */
    public function generateQuestion(
        EntryMeaning $meaning,
        string $exerciseType,
        Deck $deck,
        ?Collection $siblingMeanings = null
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
                'front_text' => ($exerciseType === 'flashcard_meaning_to_term') ? $meaning->meaning : $entry->term,
                'back_text' => ($exerciseType === 'flashcard_meaning_to_term') ? $entry->term : $meaning->meaning,
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

            'fill_in_the_blank' => array_merge($base, [
                'type' => 'fill_in_the_blank',
                'prompt' => 'Điền từ vựng thích hợp vào chỗ trống trong câu:',
                'cloze_sentence' => $this->buildClozeSentence($entry->term, $firstExample?->sentence),
                'example_translation' => $firstExample?->translation,
                'correct_answer' => $entry->term,
                'is_self_evaluated' => false,
            ]),

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
                'audio_term' => $entry->term,
                'audio_lang' => $deck->learningLanguage?->code ?? 'en',
                'correct_answer' => $entry->term,
                'is_self_evaluated' => false,
                'hide_term_text' => true,
            ]),

            'listen_and_choose' => array_merge($base, [
                'type' => 'listen_and_choose',
                'prompt' => 'Nghe phát âm và chọn nghĩa chính xác:',
                'audio_term' => $entry->term,
                'audio_lang' => $deck->learningLanguage?->code ?? 'en',
                'options' => $this->buildMeaningOptions($meaning, $siblingMeanings),
                'correct_id' => $meaning->id,
                'correct_answer' => $meaning->meaning,
                'is_self_evaluated' => false,
                'hide_term_text' => true,
            ]),

            'matching_pairs' => array_merge($base, [
                'type' => 'matching_pairs',
                'prompt' => 'Ghép từng từ ở cột trái với nghĩa tương ứng ở cột phải:',
                'pairs' => $this->buildMatchingPairs($meaning, $siblingMeanings),
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

        if (! empty($safe['hide_term_text'])) {
            unset($safe['term']);
            unset($safe['pronunciation']);
        }

        if (isset($safe['options']) && is_array($safe['options'])) {
            foreach ($safe['options'] as $idx => $opt) {
                unset($safe['options'][$idx]['is_correct']);
            }
        }

        if (isset($safe['pairs']) && is_array($safe['pairs'])) {
            // Đối với ghép cặp trong bài kiểm tra, trả về danh sách từ và danh sách nghĩa đã xáo trộn riêng biệt
            $terms = [];
            $meanings = [];
            foreach ($safe['pairs'] as $p) {
                $terms[] = ['id' => $p['id'], 'text' => $p['term']];
                $meanings[] = ['id' => $p['id'], 'text' => $p['meaning']];
            }
            shuffle($terms);
            shuffle($meanings);
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

        // Dạng nhập text: type_the_word, listen_and_type, fill_in_the_blank, word_unscramble
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
     * Chuẩn hóa văn bản để so sánh chính tả linh hoạt (xóa khoảng trắng thừa, dấu câu viền, đưa về chữ thường).
     */
    public function normalizeText(string $text): string
    {
        $trimmed = trim(mb_strtolower($text, 'UTF-8'));
        // Loại bỏ dấu câu viền
        return trim($trimmed, " .,!?;:'\"-—()[]{}");
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

