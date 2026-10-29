<?php

namespace Tests\Feature;

use App\Enums\DeckVisibility;
use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Models\Deck;
use App\Models\EntryExample;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\StudyRecord;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Models\User;
use App\Models\VocabularyEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnNewSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Language $langVi;
    protected Language $langEn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langVi = Language::factory()->create([
            'code' => 'vi',
            'name' => 'Tiếng Việt',
            'native_name' => 'Tiếng Việt',
            'is_active' => true,
        ]);

        $this->langEn = Language::factory()->create([
            'code' => 'en',
            'name' => 'Tiếng Anh',
            'native_name' => 'English',
            'is_active' => true,
        ]);
    }

    public function test_cannot_access_prepare_screen_for_private_deck_of_another_user(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'visibility' => DeckVisibility::PRIVATE,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $response = $this->actingAs($stranger)->get(route('study.new.prepare', $deck));
        $response->assertNotFound();
    }

    public function test_prepare_screen_displays_deck_info_batch_options_and_exercise_eligibility(): void
    {
        $owner = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Du học Anh',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        // Tạo 3 từ vựng với các nghĩa
        for ($i = 1; $i <= 3; $i++) {
            $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => "Term {$i}"]);
            $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => "Nghĩa {$i}"]);
            if ($i === 1) {
                // Tạo câu ví dụ cho nghĩa 1
                EntryExample::factory()->create(['entry_meaning_id' => $meaning->id, 'sentence' => 'Sample sentence with Term 1']);
            }
        }

        $response = $this->actingAs($owner)->get(route('study.new.prepare', $deck));

        $response->assertOk();
        $response->assertSee('Du học Anh');
        $response->assertSee('3 nghĩa chưa học');
        $response->assertSee('Chia thành nhóm nhỏ để làm quen rồi luyện nhớ');
        $response->assertSee('Thẻ ghi nhớ (Flashcard)');
        $response->assertSee('Trắc nghiệm: Chọn nghĩa đúng');
        $response->assertSee('Bắt đầu học ngay');

        // Dạng audio phải bị tạm khóa có lý do rõ ràng
        $response->assertSee('Chưa có tệp âm thanh thu âm hoặc dịch vụ phát âm');
    }

    public function test_starting_new_session_creates_session_and_chunks_items_by_5(): void
    {
        $owner = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        // Tạo 12 nghĩa để kiểm tra chunking (nhóm 0: 5, nhóm 1: 5, nhóm 2: 2)
        for ($i = 1; $i <= 12; $i++) {
            $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => "Word {$i}"]);
            EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => "Meaning {$i}"]);
        }

        $response = $this->actingAs($owner)->post(route('study.new.start', $deck), [
            'count' => 12,
        ]);

        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $owner->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::LearnNew->value,
            'stage' => StudyStage::Familiarization->value,
            'status' => StudySessionStatus::InProgress->value,
            'chunk_size' => 5,
            'current_chunk' => 0,
        ]);

        $session = StudySession::where('user_id', $owner->id)->first();
        $response->assertRedirect(route('study.session.show', $session));

        $this->assertCount(12, $session->items);
        $this->assertEquals(5, $session->items()->where('chunk_index', 0)->count());
        $this->assertEquals(5, $session->items()->where('chunk_index', 1)->count());
        $this->assertEquals(2, $session->items()->where('chunk_index', 2)->count());
    }

    public function test_page_reload_does_not_create_duplicate_session(): void
    {
        $owner = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Hello']);
        EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Xin chào']);

        // Gửi start lần 1
        $this->actingAs($owner)->post(route('study.new.start', $deck), ['count' => 5]);
        $this->assertEquals(1, StudySession::where('user_id', $owner->id)->count());

        // Tải lại trang hoặc gửi lại start lần 2
        $this->actingAs($owner)->post(route('study.new.start', $deck), ['count' => 5]);
        $this->assertEquals(1, StudySession::where('user_id', $owner->id)->count());
    }

    public function test_recording_familiarization_action_and_self_declared_known_rule(): void
    {
        $owner = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Water']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Nước']);

        $session = StudySession::create([
            'user_id' => $owner->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::LearnNew,
            'stage' => StudyStage::Familiarization,
            'status' => StudySessionStatus::InProgress,
            'chunk_size' => 5,
            'current_chunk' => 0,
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'sort_order' => 0,
            'chunk_index' => 0,
        ]);

        // Người dùng chọn "Tôi đã biết nghĩa này" và đánh dấu khó
        $response = $this->actingAs($owner)->postJson(route('study.session.familiarize', [
            'session' => $session,
            'item' => $item,
        ]), [
            'is_difficult' => true,
            'is_self_declared_known' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Kiểm tra StudyRecord
        $record = StudyRecord::where('user_id', $owner->id)
            ->where('entry_meaning_id', $meaning->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertTrue($record->is_self_declared_known);
        $this->assertTrue($record->is_difficult);

        // Quy tắc: Không cấp interval dài hay tự đánh giá thành thạo, chỉ cấp lịch ngắn (2 ngày)
        $this->assertEquals(2, $record->interval_days);
        $this->assertEquals(1, $record->repetition_level);
    }

    public function test_when_chunk_familiarization_completes_session_switches_to_retention_practice(): void
    {
        $owner = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Sun']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Mặt trời']);

        // Phiên chỉ có 1 item trong chunk 0
        $session = StudySession::create([
            'user_id' => $owner->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::LearnNew,
            'stage' => StudyStage::Familiarization,
            'status' => StudySessionStatus::InProgress,
            'chunk_size' => 5,
            'current_chunk' => 0,
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'sort_order' => 0,
            'chunk_index' => 0,
        ]);

        $response = $this->actingAs($owner)->postJson(route('study.session.familiarize', [
            'session' => $session,
            'item' => $item,
        ]), []);

        $response->assertOk();
        $response->assertJsonPath('data.transitioned_to_practice', true);
        $response->assertJsonPath('data.session_stage', StudyStage::RetentionPractice->value);

        $session->refresh();
        $this->assertEquals(StudyStage::RetentionPractice, $session->stage);
    }

    public function test_practice_submission_updates_spaced_repetition_record(): void
    {
        $owner = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Moon']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Mặt trăng']);

        $session = StudySession::create([
            'user_id' => $owner->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::LearnNew,
            'stage' => StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
            'chunk_size' => 5,
            'current_chunk' => 0,
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'sort_order' => 0,
            'chunk_index' => 0,
            'familiarized_at' => now(),
        ]);

        // Trả lời đúng trong bài luyện nhớ
        $response = $this->actingAs($owner)->postJson(route('study.session.practice', [
            'session' => $session,
            'item' => $item,
        ]), [
            'result' => 'pass',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $record = StudyRecord::where('user_id', $owner->id)->where('entry_meaning_id', $meaning->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->review_count);
        $this->assertEquals(1, $record->repetition_level);

        $session->refresh();
        $this->assertEquals(StudySessionStatus::Completed, $session->status);
    }

    public function test_can_pause_and_abandon_session(): void
    {
        $owner = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $owner->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $session = StudySession::create([
            'user_id' => $owner->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::LearnNew,
            'status' => StudySessionStatus::InProgress,
        ]);

        // Tạm dừng phiên
        $pauseRes = $this->actingAs($owner)->post(route('study.session.pause', $session));
        $pauseRes->assertRedirect(route('dashboard'));
        $session->refresh();
        $this->assertEquals(StudySessionStatus::Paused, $session->status);

        // Hủy / kết thúc phiên cũ
        $abandonRes = $this->actingAs($owner)->post(route('study.session.abandon', $session));
        $abandonRes->assertRedirect(route('study.new.prepare', $deck));
        $session->refresh();
        $this->assertEquals(StudySessionStatus::Abandoned, $session->status);
    }
}

