<?php

namespace Tests\Feature;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Models\Deck;
use App\Models\Language;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyHistoryTest extends TestCase
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

    public function test_history_screen_lists_only_authenticated_user_sessions(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $deck1 = Deck::factory()->create([
            'user_id' => $user1->id,
            'name' => 'User1 Deck',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $deck2 = Deck::factory()->create([
            'user_id' => $user2->id,
            'name' => 'User2 Private Deck',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        StudySession::create([
            'user_id' => $user1->id,
            'deck_id' => $deck1->id,
            'mode' => StudyMode::LearnNew,
            'status' => StudySessionStatus::Completed,
        ]);

        StudySession::create([
            'user_id' => $user2->id,
            'deck_id' => $deck2->id,
            'mode' => StudyMode::Quiz,
            'status' => StudySessionStatus::Completed,
        ]);

        $response = $this->actingAs($user1)->get(route('study.history.index'));

        $response->assertOk();
        $response->assertSee('User1 Deck');
        $response->assertDontSee('User2 Private Deck');
    }

    public function test_history_filters_by_mode(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'name' => 'Filter Deck',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        StudySession::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::LearnNew,
            'status' => StudySessionStatus::Completed,
        ]);

        StudySession::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::Quiz,
            'status' => StudySessionStatus::Completed,
        ]);

        // Lọc theo quiz
        $response = $this->actingAs($user)->get(route('study.history.index', ['mode' => 'quiz']));

        $response->assertOk();
        $response->assertSee('Kiểm tra');
    }
}

