<?php

namespace Tests\Feature;

use App\Enums\DeckVisibility;
use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\StudyRecord;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Models\User;
use App\Models\VocabularyEntry;
use App\Services\Study\QuestionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyFlowAndModesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Language $learningLang;
    protected Language $meaningLang;
    protected Deck $deck;

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
    }

    public function test_deck_show_displays_study_center_stats_and_mode_buttons(): void
    {
        // Tạo 3 từ với 4 nghĩa
        $entry1 = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Apple']);
        $meaning1 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry1->id, 'meaning' => 'Quả táo']);
        $meaning2 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry1->id, 'meaning' => 'Cây táo']);

        $entry2 = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Book']);
        $meaning3 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry2->id, 'meaning' => 'Quyển sách']);

        // Gán 1 nghĩa là đã học và đến hạn
        StudyRecord::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'entry_meaning_id' => $meaning1->id,
            'repetition_level' => 1,
            'interval_days' => 1,
            'due_at' => now()->subHours(2),
            'is_difficult' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('decks.show', $this->deck));

        $response->assertOk();
        $response->assertSee('Tiến độ & Chế độ học tập', false);
        $response->assertSee('Học từ');
        $response->assertSee('Ôn đến hạn');
        $response->assertSee('Luyện theo dạng bài');
        $response->assertSee('Luyện từ khó');
        // unstudied = 2 (meaning2, meaning3)
        $response->assertSee('2 mới');
        // due = 1
        $response->assertSee('1 đến hạn');
        // difficult = 1
        $response->assertSee('1 mục');
    }

    public function test_deck_show_disables_empty_modes_with_clear_explanations(): void
    {
        // Bộ từ chưa có từ nào
        $emptyDeck = Deck::factory()->create([
            'user_id' => $this->user->id,
            'learning_language_id' => $this->learningLang->id,
            'meaning_language_id' => $this->meaningLang->id,
            'visibility' => DeckVisibility::PRIVATE,
        ]);

        $response = $this->actingAs($this->user)->get(route('decks.show', $emptyDeck));

        $response->assertOk();
        $response->assertSee('Chưa có nghĩa mới');
        $response->assertSee('Chưa đến hạn');
        $response->assertSee('Chưa có từ khó');
    }

    public function test_guest_viewing_public_deck_is_prompted_to_login(): void
    {
        $publicDeck = Deck::factory()->create([
            'user_id' => $this->user->id,
            'learning_language_id' => $this->learningLang->id,
            'meaning_language_id' => $this->meaningLang->id,
            'visibility' => DeckVisibility::PUBLIC,
        ]);

        $response = $this->get(route('decks.show', $publicDeck));

        $response->assertOk();
        $response->assertSee('Hãy đăng nhập để theo dõi tiến độ từng nét nghĩa');
        $response->assertSee(route('login'));
    }

    public function test_user_can_prepare_and_start_practice_session(): void
    {
        $entry = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Cat']);
        EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Con mèo']);

        $response = $this->actingAs($this->user)->get(route('study.practice.deck', $this->deck));
        $response->assertOk();
        $response->assertSee('Phiên luyện này không thay đổi lịch ôn');

        $startResponse = $this->actingAs($this->user)->post(route('study.practice.start'), [
            'deck_ids' => [$this->deck->id],
            'scope' => 'all',
            'count' => 5,
            'exercise_types' => ['flashcard', 'multiple_choice_term_to_meaning'],
        ]);

        $startResponse->assertRedirect();
        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $this->user->id,
            'mode' => StudyMode::PracticeCustom->value,
            'status' => StudySessionStatus::InProgress->value,
        ]);
    }

    public function test_practice_mode_does_not_modify_srs_intervals_upon_completion(): void
    {
        $entry = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Dog']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Con chó']);

        // Bản ghi SRS ban đầu: due sau 5 ngày
        $initialDue = now()->addDays(5)->toDateTimeString();
        $record = StudyRecord::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'entry_meaning_id' => $meaning->id,
            'repetition_level' => 3,
            'interval_days' => 5,
            'due_at' => $initialDue,
        ]);

        // Tạo phiên practice
        $session = StudySession::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'mode' => StudyMode::PracticeCustom,
            'stage' => StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
            'settings' => ['is_extra_practice' => true],
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'stage' => StudyStage::RetentionPractice,
        ]);

        // Gửi kết quả luyện đúng
        $response = $this->actingAs($this->user)->postJson(
            route('study.session.practice', ['session' => $session, 'item' => $item]),
            ['result' => 'pass']
        );

        $response->assertOk();

        // Kiểm tra rằng repetition_level và interval_days và due_at KHÔNG bị sửa
        $record->refresh();
        $this->assertEquals(3, $record->repetition_level);
        $this->assertEquals(5, $record->interval_days);
        $this->assertEquals($initialDue, $record->due_at->toDateTimeString());
    }

    public function test_quick_verify_self_known_endpoint(): void
    {
        $entry = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Hello']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Xin chào']);

        $session = StudySession::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'mode' => StudyMode::LearnNew,
            'stage' => StudyStage::Familiarization,
            'status' => StudySessionStatus::InProgress,
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'stage' => StudyStage::Familiarization,
        ]);

        // 1. Yêu cầu câu hỏi kiểm tra nhanh
        $qResponse = $this->actingAs($this->user)->postJson(
            route('study.session.quick_verify', ['session' => $session, 'item' => $item])
        );
        $qResponse->assertOk()->assertJsonStructure(['success', 'question']);

        // 2. Trả lời đúng
        $verifyResponse = $this->actingAs($this->user)->postJson(
            route('study.session.quick_verify', ['session' => $session, 'item' => $item]),
            ['answer' => 'Hello']
        );
        $verifyResponse->assertOk();
        $verifyResponse->assertJson(['success' => true, 'verified' => true]);

        // Kiểm tra đã lưu self_declared_known và lịch thử thách 2 ngày
        $record = StudyRecord::where('user_id', $this->user->id)->where('entry_meaning_id', $meaning->id)->first();
        $this->assertNotNull($record);
        $this->assertTrue($record->is_self_declared_known);
        $this->assertEquals(2, $record->interval_days);
    }

    public function test_toggle_difficult_endpoint_updates_item_and_record(): void
    {
        $entry = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Complex']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Phức tạp']);

        $session = StudySession::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'mode' => StudyMode::LearnNew,
            'stage' => StudyStage::Familiarization,
            'status' => StudySessionStatus::InProgress,
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'stage' => StudyStage::Familiarization,
            'is_difficult' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            route('study.session.toggle_difficult', ['session' => $session, 'item' => $item])
        );

        $response->assertOk();
        $response->assertJson(['success' => true, 'is_difficult' => true]);

        $this->assertDatabaseHas('study_records', [
            'user_id' => $this->user->id,
            'entry_meaning_id' => $meaning->id,
            'is_difficult' => true,
        ]);
    }

    public function test_matching_pairs_realtime_check_endpoint(): void
    {
        $session = StudySession::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'mode' => StudyMode::Review,
            'stage' => StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
        ]);

        // Cặp đúng: term_id == meaning_id
        $correctResponse = $this->actingAs($this->user)->postJson(
            route('study.session.check_pair', $session),
            ['term_id' => 10, 'meaning_id' => 10]
        );
        $correctResponse->assertOk()->assertJson(['success' => true, 'is_correct' => true]);

        // Cặp sai
        $wrongResponse = $this->actingAs($this->user)->postJson(
            route('study.session.check_pair', $session),
            ['term_id' => 10, 'meaning_id' => 99]
        );
        $wrongResponse->assertOk()->assertJson(['success' => true, 'is_correct' => false]);
    }

    public function test_hint_endpoint_returns_hint_and_records_usage(): void
    {
        $entry = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Orange']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Quả cam']);

        $session = StudySession::create([
            'user_id' => $this->user->id,
            'deck_id' => $this->deck->id,
            'mode' => StudyMode::Review,
            'stage' => StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'stage' => StudyStage::RetentionPractice,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            route('study.session.hint', ['session' => $session, 'item' => $item])
        );

        $response->assertOk();
        $response->assertJsonStructure(['success', 'hint']);
        $response->assertSee('O'); // Ký tự đầu tiên của 'Orange'

        $session->refresh();
        $this->assertContains($item->id, $session->settings['hinted_items'] ?? []);
    }

    public function test_question_generator_handles_all_eight_exercise_types(): void
    {
        $generator = app(QuestionGeneratorService::class);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => 'Beautiful']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Xinh đẹp']);

        $types = [
            'flashcard',
            'multiple_choice_term_to_meaning',
            'multiple_choice_meaning_to_term',
            'matching_pairs',
            'listen_and_choose',
            'listen_and_type',
            'fill_in_the_blank',
            'word_unscramble',
        ];

        foreach ($types as $type) {
            $q = $generator->generateQuestion($meaning, $type, $this->deck);
            $this->assertNotEmpty($q['id']);
            $this->assertEquals($type, $q['exercise_type']);
        }
    }

    public function test_matching_pairs_deranges_terms_and_meanings_so_no_row_has_same_meaning(): void
    {
        $generator = app(QuestionGeneratorService::class);

        // Tạo 4 từ vựng kèm nghĩa trong bộ từ
        $entries = [
            ['term' => 'Cat', 'meaning' => 'Con mèo'],
            ['term' => 'Dog', 'meaning' => 'Con chó'],
            ['term' => 'Bird', 'meaning' => 'Con chim'],
            ['term' => 'Fish', 'meaning' => 'Con cá'],
        ];

        $meanings = [];
        foreach ($entries as $e) {
            $ent = VocabularyEntry::factory()->create(['deck_id' => $this->deck->id, 'term' => $e['term']]);
            $meanings[] = EntryMeaning::factory()->create(['vocabulary_entry_id' => $ent->id, 'meaning' => $e['meaning']]);
        }

        $targetMeaning = $meanings[0];

        // Chạy lặp lại 15 lần để kiểm chứng tính ngẫu nhiên nhưng bảo đảm 100% không bao giờ cùng hàng
        for ($run = 0; $run < 15; $run++) {
            $q = $generator->generateQuestion($targetMeaning, 'matching_pairs', $this->deck);

            $this->assertArrayHasKey('shuffled_terms', $q);
            $this->assertArrayHasKey('shuffled_meanings', $q);
            $this->assertCount(4, $q['shuffled_terms']);
            $this->assertCount(4, $q['shuffled_meanings']);

            for ($i = 0; $i < count($q['shuffled_terms']); $i++) {
                $this->assertNotEquals(
                    $q['shuffled_terms'][$i]['id'],
                    $q['shuffled_meanings'][$i]['id'],
                    "Term và Meaning tại hàng {$i} không được phép trùng nhau (lần thử {$run})."
                );
            }
        }
    }

    public function test_practice_index_prefers_deck_with_words_and_estimate_endpoint(): void
    {
        // Tạo 1 bộ từ rỗng xếp đầu theo thứ tự chữ cái
        $emptyDeck = Deck::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'AAA Empty Deck',
            'learning_language_id' => $this->learningLang->id,
            'meaning_language_id' => $this->meaningLang->id,
            'visibility' => DeckVisibility::PRIVATE,
        ]);

        // Tạo 1 bộ từ có từ vựng
        $filledDeck = Deck::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'BBB Filled Deck',
            'learning_language_id' => $this->learningLang->id,
            'meaning_language_id' => $this->meaningLang->id,
            'visibility' => DeckVisibility::PRIVATE,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $ent = VocabularyEntry::factory()->create(['deck_id' => $filledDeck->id, 'term' => "Word {$i}"]);
            EntryMeaning::factory()->create(['vocabulary_entry_id' => $ent->id, 'meaning' => "Nghia {$i}"]);
        }

        // 1. Vào trang practice index: Phải tự động chọn bộ từ có từ vựng thay vì bộ từ rỗng đầu danh sách
        $response = $this->actingAs($this->user)->get(route('study.practice.index'));
        $response->assertOk();
        $response->assertViewHas('selectedDeckIds', [$filledDeck->id]);
        $response->assertViewHas('availableMeaningsCount', 3);

        // 2. Gọi endpoint estimate khi người dùng chọn/bỏ chọn bộ từ
        $estimateResponse = $this->actingAs($this->user)->postJson(route('study.practice.estimate'), [
            'deck_ids' => [$emptyDeck->id, $filledDeck->id],
            'scope' => 'all',
        ]);

        $estimateResponse->assertOk();
        $estimateResponse->assertJson([
            'available_count' => 3,
        ]);
        $json = $estimateResponse->json();
        $this->assertTrue($json['exercise_types']['flashcard']['is_eligible']);
        $this->assertTrue($json['exercise_types']['multiple_choice_term_to_meaning']['is_eligible']);
    }
}
