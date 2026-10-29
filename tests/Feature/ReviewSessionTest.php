<?php

namespace Tests\Feature;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\StudyRecord;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Models\User;
use App\Models\VocabularyEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSessionTest extends TestCase
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

    public function test_review_index_displays_due_and_overdue_counts(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'name' => 'Review Deck',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry1 = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Word 1']);
        $meaning1 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry1->id, 'meaning' => 'Nghĩa 1']);

        $entry2 = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Word 2']);
        $meaning2 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry2->id, 'meaning' => 'Nghĩa 2']);

        // Nghĩa 1 đến hạn hôm nay (subHour)
        StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning1->id,
            'due_at' => now()->subHour(),
        ]);

        // Nghĩa 2 quá hạn > 24h (subDays(2))
        StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning2->id,
            'due_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('study.review.index'));

        $response->assertOk();
        $response->assertSee('Tổng nghĩa đến hạn');
        $response->assertSee('2'); // Tổng đến hạn là 2
        $response->assertSee('Trong đó đã quá hạn');
        $response->assertSee('1'); // Quá hạn là 1 (tập con của 2)
    }

    public function test_starting_review_session_prioritizes_oldest_due_records(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry1 = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Newer Due']);
        $meaning1 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry1->id]);

        $entry2 = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Oldest Due']);
        $meaning2 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry2->id]);

        StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning1->id,
            'due_at' => now()->subHours(2),
        ]);

        StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning2->id,
            'due_at' => now()->subDays(5), // Cũ hơn
        ]);

        $response = $this->actingAs($user)->post(route('study.review.start'), [
            'count' => 1,
        ]);

        $session = StudySession::where('user_id', $user->id)->where('mode', StudyMode::Review)->first();
        $this->assertNotNull($session);
        $response->assertRedirect(route('study.session.show', $session));

        // Mục đầu tiên phải là mục có hạn ôn cũ nhất (Oldest Due)
        $this->assertEquals($meaning2->id, $session->items->first()->entry_meaning_id);
    }

    public function test_review_schedule_policy_updates_srs_intervals_accurately(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Review Word']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Nghĩa']);

        $record = StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning->id,
            'repetition_level' => 0,
            'interval_days' => 1,
            'due_at' => now()->subHour(),
        ]);

        $session = StudySession::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::Review,
            'stage' => \App\Enums\StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
            'settings' => [
                'finalized_meanings' => [],
                'is_extra_practice' => false,
            ],
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'sort_order' => 0,
        ]);

        // Trả lời đúng lần đầu trong phiên ôn: Tăng lên bước 1 (3 ngày)
        $this->actingAs($user)->postJson(route('study.session.practice', [
            'session' => $session,
            'item' => $item,
        ]), [
            'result' => 'pass',
        ]);

        $record->refresh();
        $this->assertEquals(1, $record->repetition_level);
        $this->assertEquals(3, $record->interval_days);
        $this->assertTrue($record->due_at->greaterThan(now()->addDays(2)));

        // Khi trả lời lại trong cùng phiên, không được tiếp tục tăng bước
        $this->actingAs($user)->postJson(route('study.session.practice', [
            'session' => $session,
            'item' => $item,
        ]), [
            'result' => 'pass',
        ]);

        $record->refresh();
        $this->assertEquals(1, $record->repetition_level); // Vẫn là 1, không nhảy lên 2
    }

    public function test_extra_practice_mode_does_not_modify_srs_schedule(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Extra Practice']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id]);

        $targetDue = now()->addDays(14);

        $record = StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning->id,
            'repetition_level' => 3,
            'interval_days' => 14,
            'due_at' => $targetDue,
        ]);

        // Tạo phiên Luyện thêm ngoài lịch (is_extra_practice = true)
        $session = StudySession::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::Review,
            'stage' => \App\Enums\StudyStage::RetentionPractice,
            'status' => StudySessionStatus::InProgress,
            'settings' => [
                'is_extra_practice' => true,
                'finalized_meanings' => [],
            ],
        ]);

        $item = StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
        ]);

        $this->actingAs($user)->postJson(route('study.session.practice', [
            'session' => $session,
            'item' => $item,
        ]), [
            'result' => 'pass',
        ]);

        $record->refresh();
        // Lịch SRS không bị thay đổi vì đây là luyện tự chọn
        $this->assertEquals(3, $record->repetition_level);
        $this->assertEquals(14, $record->interval_days);
        $this->assertEquals($targetDue->timestamp, $record->due_at->timestamp);
    }
}

