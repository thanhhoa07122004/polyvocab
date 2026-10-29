<?php

namespace App\Http\Controllers\Study;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Services\Study\QuestionGeneratorService;
use App\Services\Study\StudyProgressService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(
        protected StudyProgressService $progressService,
        protected QuestionGeneratorService $questionService
    ) {}

    /**
     * Giao diện chuẩn bị kiểm tra đánh giá kiến thức.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Danh sách các bộ từ người dùng có quyền truy cập (sở hữu hoặc công khai)
        $decks = Deck::query()
            ->with(['learningLanguage', 'meaningLanguage'])
            ->withCount('entries')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('visibility', 'public');
            })
            ->latest('updated_at')
            ->get();

        // Phiên kiểm tra đang dở dang (nếu có)
        $activeQuizSession = $this->progressService->getActiveSession($user, mode: StudyMode::Quiz);

        $presets = config('polyvocab.study.quiz.count_presets', [5, 10, 20, 30]);
        $defaultCount = config('polyvocab.study.quiz.default_count', 10);
        $maxCount = config('polyvocab.study.quiz.max_count', 50);

        // Các dạng bài kiểm tra hợp lệ (không bao gồm Flashcard tự đánh giá chủ quan)
        $exercises = array_filter(
            config('polyvocab.study.exercise_types', []),
            fn ($ex, $key) => $key !== 'flashcard',
            ARRAY_FILTER_USE_BOTH
        );

        return view('study.quiz_index', [
            'decks' => $decks,
            'activeQuizSession' => $activeQuizSession,
            'presets' => $presets,
            'defaultCount' => $defaultCount,
            'maxCount' => $maxCount,
            'exercises' => $exercises,
        ]);
    }

    /**
     * Bắt đầu một bài kiểm tra mới.
     */
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'deck_ids' => ['required', 'array', 'min:1'],
            'deck_ids.*' => ['integer', 'exists:decks,id'],
            'scope' => ['required', 'string', 'in:studied,unstudied,all'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'quiz_type' => ['required', 'string', 'in:practice,formal'],
            'time_limit' => ['nullable', 'integer', 'min:0', 'max:120'],
            'shuffle' => ['nullable', 'boolean'],
            'exercise_types' => ['nullable', 'array'],
            'exercise_types.*' => ['string'],
        ]);

        $deckIds = $validated['deck_ids'];
        $scope = $validated['scope'];
        $count = (int) $validated['count'];
        $quizType = $validated['quiz_type'];
        $timeLimitMinutes = ! empty($validated['time_limit']) ? (int) $validated['time_limit'] : null;
        $exerciseTypes = $validated['exercise_types'] ?? [
            'multiple_choice_term_to_meaning',
            'multiple_choice_meaning_to_term',
            'fill_in_the_blank',
            'type_the_word',
        ];

        // Lấy danh sách nghĩa theo phạm vi lựa chọn
        $query = EntryMeaning::query()
            ->with(['vocabularyEntry.deck.learningLanguage', 'examples'])
            ->whereHas('vocabularyEntry', fn ($q) => $q->whereIn('deck_id', $deckIds));

        if ($scope === 'studied') {
            $query->whereHas('studyRecords', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($scope === 'unstudied') {
            $query->whereDoesntHave('studyRecords', fn ($q) => $q->where('user_id', $user->id));
        }

        if (! empty($validated['shuffle'])) {
            $query->inRandomOrder();
        }

        $meanings = $query->take($count)->get();

        if ($meanings->isEmpty()) {
            return redirect()
                ->route('study.quiz.index')
                ->with('error', 'Không tìm thấy từ vựng nào trong phạm vi đã chọn để tạo bài kiểm tra.');
        }

        $primaryDeckId = $meanings->first()->vocabularyEntry->deck_id;

        // Sinh cấu trúc câu hỏi đầy đủ phía server
        $questions = [];
        $allowedExercises = array_values($exerciseTypes);

        foreach ($meanings as $idx => $meaning) {
            $chosenType = $allowedExercises[$idx % count($allowedExercises)];
            $questions[] = $this->questionService->generateQuestion(
                meaning: $meaning,
                exerciseType: $chosenType,
                deck: $meaning->vocabularyEntry->deck
            );
        }

        if (! empty($validated['shuffle'])) {
            shuffle($questions);
        }

        $session = DB::transaction(function () use ($user, $primaryDeckId, $meanings, $questions, $quizType, $timeLimitMinutes, $deckIds, $scope) {
            $now = now();
            $expiresAt = $timeLimitMinutes ? $now->copy()->addMinutes($timeLimitMinutes) : null;

            $session = StudySession::create([
                'user_id' => $user->id,
                'deck_id' => $primaryDeckId,
                'mode' => StudyMode::Quiz,
                'stage' => StudyStage::RetentionPractice,
                'status' => StudySessionStatus::InProgress,
                'chunk_size' => count($questions),
                'current_chunk' => 0,
                'settings' => [
                    'quiz_type' => $quizType,
                    'time_limit_minutes' => $timeLimitMinutes,
                    'started_at' => $now->toIso8601String(),
                    'expires_at' => $expiresAt ? $expiresAt->toIso8601String() : null,
                    'scope' => $scope,
                    'deck_ids' => $deckIds,
                    'questions' => $questions,
                    'answers' => [],
                    'flagged' => [],
                    'hints_used' => [],
                ],
                'last_interacted_at' => $now,
            ]);

            foreach ($meanings->values() as $index => $meaning) {
                StudySessionItem::create([
                    'study_session_id' => $session->id,
                    'entry_meaning_id' => $meaning->id,
                    'sort_order' => $index,
                    'chunk_index' => 0,
                    'stage' => StudyStage::RetentionPractice,
                ]);
            }

            return $session;
        });

        return redirect()->route('study.quiz.show', $session);
    }

    /**
     * Màn hình làm bài kiểm tra.
     */
    public function show(Request $request, StudySession $session): View|RedirectResponse
    {
        if ($session->user_id !== $request->user()->id || $session->mode !== StudyMode::Quiz) {
            abort(403);
        }

        if ($session->status === StudySessionStatus::Completed) {
            return redirect()->route('study.quiz.result', $session);
        }

        $settings = $session->settings ?: [];
        $expiresAtStr = $settings['expires_at'] ?? null;

        // Kiểm tra hết giờ phía server
        if ($expiresAtStr && now()->greaterThan(Carbon::parse($expiresAtStr))) {
            return $this->finalizeQuizSubmission($session, 'Hết thời gian làm bài.');
        }

        $quizType = $settings['quiz_type'] ?? 'formal';
        $rawQuestions = $settings['questions'] ?? [];

        // Nếu là bài kiểm tra chính thức (formal), loại bỏ toàn bộ đáp án đúng trước khi trả về client
        $safeQuestions = [];
        foreach ($rawQuestions as $idx => $q) {
            $safeQuestions[$idx] = ($quizType === 'formal')
                ? $this->questionService->stripAnswersForClient($q)
                : $q;
        }

        return view('study.quiz_runner', [
            'session' => $session,
            'quizType' => $quizType,
            'questions' => $safeQuestions,
            'answers' => $settings['answers'] ?? [],
            'flagged' => $settings['flagged'] ?? [],
            'hintsUsed' => $settings['hints_used'] ?? [],
            'expiresAt' => $expiresAtStr ? Carbon::parse($expiresAtStr) : null,
            'timeLimitMinutes' => $settings['time_limit_minutes'] ?? null,
            'totalCount' => count($safeQuestions),
        ]);
    }

    /**
     * API lưu đáp án từng câu (Auto-save) và trạng thái đánh dấu xem lại.
     */
    public function saveAnswer(Request $request, StudySession $session): JsonResponse
    {
        if ($session->user_id !== $request->user()->id || $session->mode !== StudyMode::Quiz) {
            abort(403);
        }

        $settings = $session->settings ?: [];
        $expiresAtStr = $settings['expires_at'] ?? null;

        // Kiểm tra hết giờ phía server: từ chối sửa đáp án khi đã hết hạn
        if ($expiresAtStr && now()->greaterThan(Carbon::parse($expiresAtStr))) {
            return response()->json([
                'success' => false,
                'expired' => true,
                'message' => 'Bài kiểm tra đã hết thời gian làm bài. Đáp án không còn được ghi nhận.',
            ], 422);
        }

        $validated = $request->validate([
            'question_index' => ['required', 'integer', 'min:0'],
            'answer' => ['nullable'],
            'is_flagged' => ['nullable', 'boolean'],
            'used_hint' => ['nullable', 'boolean'],
        ]);

        $qIdx = (int) $validated['question_index'];
        $answers = $settings['answers'] ?? [];
        $flagged = $settings['flagged'] ?? [];
        $hintsUsed = $settings['hints_used'] ?? [];

        if (array_key_exists('answer', $validated)) {
            $answers[$qIdx] = $validated['answer'];
        }

        if (array_key_exists('is_flagged', $validated)) {
            $flagged[$qIdx] = (bool) $validated['is_flagged'];
        }

        if (! empty($validated['used_hint'])) {
            $hintsUsed[$qIdx] = true;
        }

        $settings['answers'] = $answers;
        $settings['flagged'] = $flagged;
        $settings['hints_used'] = $hintsUsed;

        $session->settings = $settings;
        $session->last_interacted_at = now();
        $session->save();

        return response()->json([
            'success' => true,
            'status' => 'saved',
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Nộp bài kiểm tra.
     */
    public function submit(Request $request, StudySession $session): RedirectResponse
    {
        if ($session->user_id !== $request->user()->id || $session->mode !== StudyMode::Quiz) {
            abort(403);
        }

        if ($session->status === StudySessionStatus::Completed) {
            return redirect()->route('study.quiz.result', $session);
        }

        return $this->finalizeQuizSubmission($session);
    }

    /**
     * Màn hình hiển thị kết quả kiểm tra chi tiết.
     */
    public function result(Request $request, StudySession $session): View
    {
        if ($session->user_id !== $request->user()->id || $session->mode !== StudyMode::Quiz) {
            abort(403);
        }

        $settings = $session->settings ?: [];
        $summary = $settings['score_summary'] ?? [];
        $evaluatedResults = $settings['evaluated_results'] ?? [];
        $questions = $settings['questions'] ?? [];

        return view('study.quiz_result', [
            'session' => $session,
            'summary' => $summary,
            'evaluatedResults' => $evaluatedResults,
            'questions' => $questions,
            'quizType' => $settings['quiz_type'] ?? 'formal',
        ]);
    }

    /**
     * Chấm điểm và hoàn tất bài kiểm tra (idempotent).
     */
    protected function finalizeQuizSubmission(StudySession $session, ?string $reason = null): RedirectResponse
    {
        $settings = $session->settings ?: [];
        $questions = $settings['questions'] ?? [];
        $answers = $settings['answers'] ?? [];
        $hintsUsed = $settings['hints_used'] ?? [];

        $totalQuestions = count($questions);
        $totalScoreAchieved = 0.0;
        $correctCount = 0;
        $incorrectCount = 0;
        $emptyCount = 0;
        $hintedCount = 0;
        $evaluatedResults = [];
        $typeBreakdown = [];

        foreach ($questions as $idx => $q) {
            $userAns = $answers[$idx] ?? null;
            $hasUsedHint = ! empty($hintsUsed[$idx]);
            $type = $q['type'] ?? 'multiple_choice';

            $grade = $this->questionService->gradeAnswer($q, $userAns);

            // Quy tắc: Nếu đã dùng gợi ý trong chế độ luyện, câu đó nhận 0 điểm
            if ($hasUsedHint) {
                $hintedCount++;
                $grade['score'] = 0.0;
                $grade['is_correct'] = false;
                $grade['feedback'] .= ' (Đã sử dụng gợi ý: không tính điểm).';
            }

            if ($grade['is_empty'] ?? false) {
                $emptyCount++;
            } elseif ($grade['is_correct']) {
                $correctCount++;
            } else {
                $incorrectCount++;
            }

            $totalScoreAchieved += $grade['score'];
            $evaluatedResults[$idx] = array_merge($grade, [
                'used_hint' => $hasUsedHint,
            ]);

            // Thống kê theo dạng bài
            if (! isset($typeBreakdown[$type])) {
                $typeBreakdown[$type] = ['total' => 0, 'correct' => 0, 'score' => 0.0];
            }
            $typeBreakdown[$type]['total']++;
            if ($grade['is_correct']) {
                $typeBreakdown[$type]['correct']++;
            }
            $typeBreakdown[$type]['score'] += $grade['score'];
        }

        $percentage = $totalQuestions > 0 ? round(($totalScoreAchieved / $totalQuestions) * 100, 1) : 0.0;

        $startedAt = ! empty($settings['started_at']) ? Carbon::parse($settings['started_at']) : $session->created_at;
        $durationSeconds = max(1, now()->diffInSeconds($startedAt));

        $settings['evaluated_results'] = $evaluatedResults;
        $settings['score_summary'] = [
            'total_questions' => $totalQuestions,
            'score_achieved' => round($totalScoreAchieved, 2),
            'percentage' => $percentage,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'empty_count' => $emptyCount,
            'hinted_count' => $hintedCount,
            'type_breakdown' => $typeBreakdown,
            'duration_seconds' => $durationSeconds,
            'finish_reason' => $reason,
        ];

        $session->status = StudySessionStatus::Completed;
        $session->settings = $settings;
        $session->last_interacted_at = now();
        $session->save();

        return redirect()
            ->route('study.quiz.result', $session)
            ->with('status', $reason ?: 'Bài kiểm tra đã được chấm điểm thành công.');
    }
}
