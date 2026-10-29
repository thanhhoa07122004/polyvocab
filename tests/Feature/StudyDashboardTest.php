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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyDashboardTest extends TestCase
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

    public function test_dashboard_shows_core_modes_and_progress_unit_principle(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Học từ mới');
        $response->assertSee('Ôn tập');
        $response->assertSee('Kiểm tra');
        $response->assertSee('Một từ có thể có nhiều nghĩa — mỗi nghĩa là một đơn vị tiến độ riêng');
    }

    public function test_dashboard_shows_empty_state_when_user_has_no_decks(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Bạn chưa tạo bộ từ vựng nào');
        $response->assertSee('Khám phá bộ từ cộng đồng');
        $response->assertSee(route('decks.create'));
    }

    public function test_dashboard_shows_guidance_when_user_has_decks_without_words(): void
    {
        $user = User::factory()->create();

        // Tạo 1 bộ từ chưa có từ vựng nào
        Deck::factory()->create([
            'user_id' => $user->id,
            'name' => 'Từ vựng tiếng Anh rỗng',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Các bộ từ của bạn hiện chưa có từ vựng');
        $response->assertSee('Quản lý bộ từ');
        $response->assertSee('thêm từ mới');
    }

    public function test_dashboard_shows_accurate_due_count_and_no_fake_metrics(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user1->id,
            'name' => 'Oxford 3000',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry1 = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Apple']);
        $meaning1 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry1->id, 'meaning' => 'Quả táo']);

        $entry2 = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Book']);
        $meaning2 = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry2->id, 'meaning' => 'Quyển sách']);

        // Ghi nhận nghĩa 1 đã đến hạn cho user1
        StudyRecord::create([
            'user_id' => $user1->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning1->id,
            'due_at' => now()->subHour(),
        ]);

        // Ghi nhận nghĩa 2 chưa đến hạn (ngày mai) cho user1
        StudyRecord::create([
            'user_id' => $user1->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning2->id,
            'due_at' => now()->addDay(),
        ]);

        // Dashboard của user1 phải thấy chính xác 1 nghĩa đến hạn
        $response1 = $this->actingAs($user1)->get(route('dashboard'));
        $response1->assertOk();
        $response1->assertSee('1 nghĩa đến hạn');
        $response1->assertSee('Ôn tập ngay (1)');

        // Dashboard của user2 phải thấy 0 đến hạn, không dính dữ liệu của user1
        $response2 = $this->actingAs($user2)->get(route('dashboard'));
        $response2->assertOk();
        $response2->assertSee('0 đến hạn');
        $response2->assertSee('Chưa có lịch ôn tập');
    }

    public function test_dashboard_shows_next_review_time_when_no_meanings_are_currently_due(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'name' => 'IELTS Vocab',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Resilient']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Kiên cường']);

        $futureDate = now()->addDays(3);

        StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning->id,
            'due_at' => $futureDate,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('0 đến hạn');
        $response->assertSee('Lịch ôn tập gần nhất tiếp theo là vào');
        $response->assertSee($futureDate->format('d/m/Y'));
    }

    public function test_dashboard_shows_active_session_card_with_resume_button(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'name' => 'TOEIC Essential',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Invoice']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Hóa đơn']);

        $session = StudySession::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::LearnNew,
            'stage' => StudyStage::Familiarization,
            'status' => StudySessionStatus::InProgress,
            'chunk_size' => 5,
            'current_chunk' => 0,
            'last_interacted_at' => now(),
        ]);

        StudySessionItem::create([
            'study_session_id' => $session->id,
            'entry_meaning_id' => $meaning->id,
            'sort_order' => 0,
            'chunk_index' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Phiên chưa kết thúc');
        $response->assertSee('TOEIC Essential');
        $response->assertSee('Tiếp tục phiên học');
        $response->assertSee(route('study.session.show', $session));
    }
}
