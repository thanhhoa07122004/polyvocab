<?php

namespace Tests\Feature;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\StudyRecord;
use App\Models\StudySession;
use App\Models\User;
use App\Models\VocabularyEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAssessmentTest extends TestCase
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

    public function test_quiz_index_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        Deck::factory()->create([
            'user_id' => $user->id,
            'name' => 'Quiz Deck',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $response = $this->actingAs($user)->get(route('study.quiz.index'));

        $response->assertOk();
        $response->assertSee('Kiểm tra đánh giá kiến thức');
        $response->assertSee('Quiz Deck');
        $response->assertSee('Bài kiểm tra (Formal Test)');
        $response->assertSee('Luyện kiểm tra (Practice Quiz)');
    }

    public function test_formal_test_does_not_reveal_answers_in_html_or_json(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'SecretWord']);
        $meaning = EntryMeaning::factory()->create([
            'vocabulary_entry_id' => $entry->id,
            'meaning' => 'SecretMeaningDefinition',
        ]);

        // Tạo bài kiểm tra chế độ formal
        $startRes = $this->actingAs($user)->post(route('study.quiz.start'), [
            'deck_ids' => [$deck->id],
            'scope' => 'all',
            'count' => 1,
            'quiz_type' => 'formal',
        ]);

        $session = StudySession::where('user_id', $user->id)->where('mode', StudyMode::Quiz)->first();
        $this->assertNotNull($session);

        $showRes = $this->actingAs($user)->get(route('study.quiz.show', $session));
        $showRes->assertOk();

        // Trong HTML trả về thí sinh, không chứa trường đáp án đúng lộ liễu
        $showRes->assertDontSee('"is_correct":true', false);
    }

    public function test_auto_save_answer_and_flag_for_review(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Cat']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Con mèo']);

        $session = StudySession::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::Quiz,
            'status' => StudySessionStatus::InProgress,
            'settings' => [
                'quiz_type' => 'formal',
                'questions' => [
                    [
                        'id' => 'q1',
                        'type' => 'type_the_word',
                        'correct_answer' => 'Cat',
                    ],
                ],
                'answers' => [],
                'flagged' => [],
            ],
        ]);

        $saveRes = $this->actingAs($user)->postJson(route('study.quiz.save_answer', $session), [
            'question_index' => 0,
            'answer' => 'Cat',
            'is_flagged' => true,
        ]);

        $saveRes->assertOk();
        $saveRes->assertJson(['success' => true, 'status' => 'saved']);

        $session->refresh();
        $this->assertEquals('Cat', $session->settings['answers'][0]);
        $this->assertTrue($session->settings['flagged'][0]);
    }

    public function test_submitting_quiz_calculates_scores_and_does_not_modify_srs_records(): void
    {
        $user = User::factory()->create();

        $deck = Deck::factory()->create([
            'user_id' => $user->id,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $entry = VocabularyEntry::factory()->create(['deck_id' => $deck->id, 'term' => 'Dog']);
        $meaning = EntryMeaning::factory()->create(['vocabulary_entry_id' => $entry->id, 'meaning' => 'Con chó']);

        $initialDue = now()->addDays(5);
        $record = StudyRecord::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'entry_meaning_id' => $meaning->id,
            'repetition_level' => 2,
            'interval_days' => 5,
            'due_at' => $initialDue,
        ]);

        $session = StudySession::create([
            'user_id' => $user->id,
            'deck_id' => $deck->id,
            'mode' => StudyMode::Quiz,
            'status' => StudySessionStatus::InProgress,
            'settings' => [
                'quiz_type' => 'formal',
                'questions' => [
                    [
                        'id' => 'q1',
                        'type' => 'type_the_word',
                        'prompt' => 'Gõ từ:',
                        'correct_answer' => 'Dog',
                    ],
                    [
                        'id' => 'q2',
                        'type' => 'type_the_word',
                        'prompt' => 'Gõ từ:',
                        'correct_answer' => 'Sun',
                    ],
                ],
                'answers' => [
                    0 => 'Dog', // Đúng
                    1 => 'Moon', // Sai
                ],
                'flagged' => [],
                'hints_used' => [],
            ],
        ]);

        $submitRes = $this->actingAs($user)->post(route('study.quiz.submit', $session));
        $submitRes->assertRedirect(route('study.quiz.result', $session));

        $session->refresh();
        $this->assertEquals(StudySessionStatus::Completed, $session->status);
        $summary = $session->settings['score_summary'];

        // Điểm 1/2 = 50%
        $this->assertEquals(50.0, $summary['percentage']);
        $this->assertEquals(1, $summary['correct_count']);
        $this->assertEquals(1, $summary['incorrect_count']);

        // Quy tắc: Kết quả kiểm tra KHÔNG tự động thay đổi lịch ôn SRS
        $record->refresh();
        $this->assertEquals(2, $record->repetition_level);
        $this->assertEquals($initialDue->timestamp, $record->due_at->timestamp);
    }
}

