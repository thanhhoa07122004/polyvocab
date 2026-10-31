<?php

namespace Tests\Feature;

use App\Enums\DeckVisibility;
use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Models\AiGeneratedCloze;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\StudyRecord;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Models\User;
use App\Models\VocabularyEntry;
use App\Services\Ai\AiSentenceService;
use App\Services\Study\QuestionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiClozeSentenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Language $learningLang;
    protected Language $meaningLang;
    protected Deck $deck;
    protected VocabularyEntry $entry;
    protected EntryMeaning $meaning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->learningLang = Language::factory()->create(['code' => 'en', 'name' => 'Tiếng Anh']);
        $this->meaningLang = Language::factory()->create(['code' => 'vi', 'name' => 'Tiếng Việt']);

        $this->deck = Deck::factory()->create([
            'user_id' => $this->user->id,
            'learning_language_id' => $this->learningLang->id,
            'meaning_language_id' => $this->meaningLang->id,
            'visibility' => DeckVisibility::PRIVATE,
        ]);

        $this->entry = VocabularyEntry::factory()->create([
            'deck_id' => $this->deck->id,
            'term' => 'apple',
            'pronunciation' => 'ˈæp.əl',
        ]);

        $this->meaning = EntryMeaning::factory()->create([
            'vocabulary_entry_id' => $this->entry->id,
            'meaning' => 'quả táo',
            'part_of_speech' => 'noun',
        ]);
    }

    public function test_ai_sentence_service_generates_and_caches_cloze(): void
    {
        Config::set('services.ai.enabled', true);
        Config::set('services.ai.provider', 'openai');
        Config::set('services.ai.key', 'test-fake-key');
        Config::set('services.ai.model', 'gpt-4o-mini');

        $mockPayload = [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'sentence_full' => 'She picked a ripe red apple from the tree.',
                            'sentence_with_blank' => 'She picked a ripe red [ ______ ] from the tree.',
                            'blank_answer' => 'apple',
                            'acceptable_variants' => ['apples'],
                            'translation' => 'Cô ấy đã hái một quả táo đỏ chín từ trên cây.',
                            'explanation' => '"Apple" là danh từ chỉ quả táo, phù hợp với ngữ cảnh hái từ trên cây.',
                            'distractors' => ['banana', 'orange', 'grape'],
                        ]),
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://api.openai.com/*' => Http::response($mockPayload, 200),
        ]);

        $service = app(AiSentenceService::class);
        $this->assertTrue($service->isConfigured());

        $cloze1 = $service->getOrCreateCloze($this->meaning);

        $this->assertNotNull($cloze1);
        $this->assertEquals('apple', $cloze1->blank_answer);
        $this->assertCount(3, $cloze1->distractors);
        $this->assertDatabaseHas('ai_generated_clozes', [
            'entry_meaning_id' => $this->meaning->id,
            'blank_answer' => 'apple',
        ]);

        // Lần gọi thứ 2 phải tái sử dụng từ database, không gọi thêm HTTP
        Http::fake([
            'https://api.openai.com/*' => Http::response([], 500),
        ]);

        $cloze2 = $service->getOrCreateCloze($this->meaning);
        $this->assertEquals($cloze1->id, $cloze2->id);
    }

    public function test_ai_sentence_service_generates_with_gemini_provider(): void
    {
        Config::set('services.ai.enabled', true);
        Config::set('services.ai.provider', 'gemini');
        Config::set('services.ai.key', 'test-gemini-key');
        Config::set('services.ai.model', 'gemini-1.5-flash');

        $geminiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'sentence_full' => 'He sliced a fresh green apple.',
                                    'sentence_with_blank' => 'He sliced a fresh green [ ______ ].',
                                    'blank_answer' => 'apple',
                                    'acceptable_variants' => [],
                                    'translation' => 'Anh ấy đã thái một quả táo xanh tươi.',
                                    'explanation' => 'Táo là loại hoa quả phù hợp ngữ cảnh thái nhỏ.',
                                    'distractors' => ['table', 'chair', 'spoon'],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response($geminiResponse, 200),
        ]);

        $service = app(AiSentenceService::class);
        $cloze = $service->getOrCreateCloze($this->meaning, forceNew: true);

        $this->assertNotNull($cloze);
        $this->assertEquals('apple', $cloze->blank_answer);
        $this->assertCount(3, $cloze->distractors);
    }

    public function test_question_generator_builds_cloze_choice_and_input_formats(): void
    {
        Config::set('services.ai.enabled', true);
        Config::set('services.ai.key', 'test-fake-key');

        $cloze = AiGeneratedCloze::create([
            'entry_meaning_id' => $this->meaning->id,
            'learning_language_id' => $this->learningLang->id,
            'term' => 'apple',
            'meaning' => 'quả táo',
            'sentence_full' => 'I ate an apple for breakfast.',
            'sentence_with_blank' => 'I ate an [ ______ ] for breakfast.',
            'blank_answer' => 'apple',
            'acceptable_variants' => ['apples'],
            'translation' => 'Tôi đã ăn một quả táo cho bữa sáng.',
            'explanation' => 'Dùng "apple" để chỉ quả táo.',
            'distractors' => ['orange', 'banana', 'lemon'],
        ]);

        $generator = app(QuestionGeneratorService::class);

        // Trường hợp 1: Tỉ lệ choice = 1.0 (Trắc nghiệm 4 đáp án)
        Config::set('polyvocab.ai_cloze.choice_ratio', 1.0);
        $qChoice = $generator->generateQuestion(
            $this->meaning,
            'fill_in_the_blank',
            $this->deck,
            null,
            ['use_ai_cloze' => true]
        );

        $this->assertEquals('fill_in_the_blank', $qChoice['type']);
        $this->assertEquals('choice', $qChoice['cloze_format']);
        $this->assertTrue($qChoice['is_ai_generated']);
        $this->assertCount(4, $qChoice['options']);
        $optionTexts = collect($qChoice['options'])->pluck('text')->all();
        $this->assertContains('apple', $optionTexts);
        $this->assertContains('orange', $optionTexts);

        // Trường hợp 2: Tỉ lệ choice = 0.0 (Nhập từ)
        Config::set('polyvocab.ai_cloze.choice_ratio', 0.0);
        $qInput = $generator->generateQuestion(
            $this->meaning,
            'fill_in_the_blank',
            $this->deck,
            null,
            ['use_ai_cloze' => true]
        );

        $this->assertEquals('input', $qInput['cloze_format']);
        $this->assertArrayNotHasKey('options', $qInput);
        $this->assertEquals('apple', $qInput['blank_answer']);

        // Trường hợp 3: Không đủ 3 phương án nhiễu -> fallback về input
        $cloze->update(['distractors' => ['orange']]);
        Config::set('polyvocab.ai_cloze.choice_ratio', 1.0);
        $qFallback = $generator->generateQuestion(
            $this->meaning,
            'fill_in_the_blank',
            $this->deck,
            null,
            ['use_ai_cloze' => true]
        );
        $this->assertEquals('input', $qFallback['cloze_format']);
    }

    public function test_server_grading_with_unicode_nfc_and_acceptable_variants(): void
    {
        $generator = app(QuestionGeneratorService::class);

        $question = [
            'type' => 'fill_in_the_blank',
            'cloze_format' => 'input',
            'blank_answer' => 'caffè',
            'acceptable_variants' => ['coffee', 'cafe'],
            'sentence_full' => 'I would like a cup of caffè.',
            'explanation' => 'Caffè có nghĩa là cà phê.',
        ];

        // 1. Đúng chính xác
        $res1 = $generator->gradeAnswer($question, 'caffè');
        $this->assertTrue($res1['is_correct']);
        $this->assertEquals(1.0, $res1['score']);

        // 2. Đúng với chữ hoa và khoảng trắng thừa
        $res2 = $generator->gradeAnswer($question, '   CAFFÈ  ');
        $this->assertTrue($res2['is_correct']);

        // 3. Đúng với biến thể được chấp nhận (variant)
        $res3 = $generator->gradeAnswer($question, 'coffee');
        $this->assertTrue($res3['is_correct']);

        // 4. Sai chính tả
        $res4 = $generator->gradeAnswer($question, 'tea');
        $this->assertFalse($res4['is_correct']);
        $this->assertEquals(0.0, $res4['score']);
    }

    public function test_strip_answers_for_client_does_not_leak_answers(): void
    {
        $generator = app(QuestionGeneratorService::class);

        $question = [
            'type' => 'fill_in_the_blank',
            'cloze_format' => 'choice',
            'cloze_sentence' => 'She has an [ ______ ].',
            'blank_answer' => 'apple',
            'correct_answer' => 'apple',
            'sentence_full' => 'She has an apple.',
            'acceptable_variants' => ['apples'],
            'example_translation' => 'Cô ấy có một quả táo.',
            'explanation' => 'Giải thích chi tiết.',
            'options' => [
                ['id' => '1', 'text' => 'apple', 'is_correct' => true],
                ['id' => '2', 'text' => 'banana', 'is_correct' => false],
            ],
        ];

        $stripped = $generator->stripAnswersForClient($question);

        $this->assertArrayNotHasKey('correct_answer', $stripped);
        $this->assertArrayNotHasKey('blank_answer', $stripped);
        $this->assertArrayNotHasKey('sentence_full', $stripped);
        $this->assertArrayNotHasKey('acceptable_variants', $stripped);
        $this->assertArrayNotHasKey('example_translation', $stripped);
        $this->assertArrayNotHasKey('explanation', $stripped);

        foreach ($stripped['options'] as $opt) {
            $this->assertArrayNotHasKey('is_correct', $opt);
        }
    }

    public function test_practice_session_with_ai_cloze_submission_and_stability(): void
    {
        Config::set('services.ai.enabled', true);
        Config::set('services.ai.key', 'test-fake-key');

        AiGeneratedCloze::create([
            'entry_meaning_id' => $this->meaning->id,
            'learning_language_id' => $this->learningLang->id,
            'term' => 'apple',
            'meaning' => 'quả táo',
            'sentence_full' => 'An apple a day keeps the doctor away.',
            'sentence_with_blank' => 'An [ ______ ] a day keeps the doctor away.',
            'blank_answer' => 'apple',
            'acceptable_variants' => ['Apple'],
            'translation' => 'Mỗi ngày một quả táo giúp tránh xa bác sĩ.',
            'explanation' => 'Thành ngữ tiếng Anh kinh điển.',
            'distractors' => ['orange', 'peach', 'mango'],
        ]);

        // Tạo phiên luyện tập bật AI cloze
        $session = StudySession::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'mode' => StudyMode::PracticeCustom,
            'stage' => StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
            'settings' => [
                'exercise_types' => ['fill_in_the_blank'],
                'use_ai_cloze' => true,
                'is_extra_practice' => true,
            ],
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $this->meaning->id,
            'sort_order' => 0,
            'stage' => StudyStage::RetentionPractice,
            'familiarized_at' => now(),
        ]);

        // Xem phiên học -> tạo và cache câu hỏi
        $responseShow = $this->actingAs($this->user)->get(route('study.session.show', $session));
        $responseShow->assertOk();
        $responseShow->assertSee('✨ Câu do AI tạo', false);

        $session->refresh();
        $cachedQuestion = $session->settings['item_questions'][$item->id] ?? null;
        $this->assertNotNull($cachedQuestion);
        $this->assertEquals('fill_in_the_blank', $cachedQuestion['type']);

        // Nộp câu trả lời đúng lên route practice
        $responsePractice = $this->actingAs($this->user)->postJson(
            route('study.session.practice', ['session' => $session, 'item' => $item]),
            [
                'user_answer' => 'apple',
            ]
        );

        $responsePractice->assertOk();
        $data = $responsePractice->json();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['grading']['is_correct']);
        $this->assertEquals('An apple a day keeps the doctor away.', $data['grading']['sentence_full']);
        $this->assertNotEmpty($data['grading']['example_translation']);
        $this->assertNotEmpty($data['grading']['explanation']);

        // Đảm bảo không thay đổi lịch ôn SRS trong Practice mode
        $record = StudyRecord::where('user_id', $this->user->id)
            ->where('entry_meaning_id', $this->meaning->id)
            ->first();
        $this->assertNull($record->due_at ?? null);
    }

    public function test_review_session_with_ai_cloze_updates_srs_correctly(): void
    {
        Config::set('services.ai.enabled', true);
        Config::set('services.ai.key', 'test-fake-key');

        AiGeneratedCloze::create([
            'entry_meaning_id' => $this->meaning->id,
            'learning_language_id' => $this->learningLang->id,
            'term' => 'apple',
            'meaning' => 'quả táo',
            'sentence_full' => 'I love eating an apple.',
            'sentence_with_blank' => 'I love eating an [ ______ ].',
            'blank_answer' => 'apple',
            'acceptable_variants' => [],
            'translation' => 'Tôi thích ăn táo.',
            'explanation' => 'Giải thích táo.',
            'distractors' => ['pear', 'plum', 'grape'],
        ]);

        $studyRecord = StudyRecord::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'entry_meaning_id' => $this->meaning->id,
            'repetition_level' => 1,
            'interval_days' => 1,
            'due_at' => now()->subDay(),
        ]);

        // Bắt đầu phiên ôn tập với AI cloze
        $responseStart = $this->actingAs($this->user)->post(route('study.review.start'), [
            'deck_ids' => [$this->deck->id],
            'count' => 1,
            'exercise_types' => ['fill_in_the_blank'],
            'use_ai_cloze' => 1,
        ]);

        $responseStart->assertRedirect();
        $session = StudySession::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertTrue($session->settings['use_ai_cloze']);

        $item = $session->items->first();

        // Gửi đáp án đúng
        $responsePractice = $this->actingAs($this->user)->postJson(
            route('study.session.practice', ['session' => $session, 'item' => $item]),
            [
                'user_answer' => 'apple',
            ]
        );

        $responsePractice->assertOk();
        $this->assertTrue($responsePractice->json('grading.is_correct'));

        // Kiểm tra lịch SRS được cập nhật tăng interval
        $studyRecord->refresh();
        $this->assertGreaterThan(1, $studyRecord->repetition_level);
        $this->assertNotNull($studyRecord->due_at);
        $this->assertTrue($studyRecord->due_at->isFuture());
    }

    public function test_fill_in_the_blank_hint_returns_clean_sentence_translation(): void
    {
        $session = StudySession::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'mode' => StudyMode::PracticeCustom,
            'stage' => StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
            'settings' => [
                'exercise_types' => ['fill_in_the_blank'],
            ],
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $this->meaning->id,
            'sort_order' => 0,
            'stage' => StudyStage::RetentionPractice,
        ]);

        // $this->meaning->meaning là "quả táo"
        $session->settings = [
            'exercise_types' => ['fill_in_the_blank'],
            'item_questions' => [
                $item->id => [
                    'type' => 'fill_in_the_blank',
                    'cloze_sentence' => 'She has an [ ______ ].',
                    'example_translation' => 'Cô ấy có một quả táo đỏ tươi.',
                ],
            ],
        ];
        $session->save();

        $response = $this->actingAs($this->user)->postJson(
            route('study.session.hint', ['session' => $session, 'item' => $item])
        );

        $response->assertOk();
        $hint = $response->json('hint');
        $this->assertStringContainsString('Dịch câu:', $hint);
        // Chỗ từ quả táo cần được che bằng [ ______ ] trong câu dịch gợi ý
        $this->assertStringContainsString('[ ______ ]', $hint);
        $this->assertStringNotContainsString('quả táo', $hint);
        $this->assertStringContainsString('Cô ấy có một [ ______ ] đỏ tươi', $hint);

        // Kiểm tra với từ yellow nghĩa là màu vàng như người dùng phản hồi
        $generator = app(QuestionGeneratorService::class);
        $maskedYellow = $generator->buildClozeTranslation(
            'Những chiếc lá mùa thu chuyển sang màu vàng tươi trước khi rơi khỏi cây.',
            'màu vàng',
            'yellow'
        );
        $this->assertEquals(
            'Những chiếc lá mùa thu chuyển sang [ ______ ] tươi trước khi rơi khỏi cây.',
            $maskedYellow
        );
    }
}
