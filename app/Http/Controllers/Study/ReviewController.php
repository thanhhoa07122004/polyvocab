<?php

namespace App\Http\Controllers\Study;

use App\Actions\Study\CreateReviewSessionAction;
use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Models\Language;
use App\Models\StudySession;
use App\Services\Study\ExerciseEligibilityService;
use App\Services\Study\StudyProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        protected StudyProgressService $progressService,
        protected ExerciseEligibilityService $eligibilityService,
        protected CreateReviewSessionAction $createReviewAction
    ) {}

    /**
     * Giao diện chuẩn bị ôn tập (kèm bộ lọc, số lượng đến hạn, quá hạn và chế độ Luyện thêm).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $selectedDeckId = $request->query('deck_id') ? (int) $request->query('deck_id') : null;
        $selectedLanguageId = $request->query('language_id') ? (int) $request->query('language_id') : null;

        $deckIds = $selectedDeckId ? [$selectedDeckId] : null;

        $totalDue = $this->progressService->getDueMeaningsCount($user, $selectedDeckId ? Deck::find($selectedDeckId) : null);
        $overdueCount = $this->progressService->getOverdueMeaningsCount($user, $selectedDeckId ? Deck::find($selectedDeckId) : null);
        $nextDueAt = $totalDue === 0 ? $this->progressService->getNextDueReviewTime($user) : null;
        $hasStudied = $this->progressService->hasAnyStudiedMeanings($user);

        // Lấy phiên ôn tập đang dở dang (nếu có)
        $activeReviewSession = $this->progressService->getActiveSession($user, mode: StudyMode::Review);

        // Danh sách bộ từ của người dùng để lọc
        $userDecks = $user->decks()
            ->with(['learningLanguage', 'meaningLanguage'])
            ->get()
            ->map(function (Deck $d) use ($user) {
                $d->due_count = $this->progressService->getDueMeaningsCount($user, $d);
                $d->overdue_count = $this->progressService->getOverdueMeaningsCount($user, $d);
                return $d;
            });

        // Danh sách ngôn ngữ hoạt động
        $languages = Language::active()->get();

        // 8 dạng bài học
        $exercises = config('polyvocab.study.exercise_types', []);

        $presets = [5, 10, 20];
        if ($totalDue > 0 && ! in_array($totalDue, $presets, true)) {
            $presets[] = $totalDue;
            sort($presets);
        }

        return view('study.review_index', [
            'totalDue' => $totalDue,
            'overdueCount' => $overdueCount,
            'nextDueAt' => $nextDueAt,
            'hasStudied' => $hasStudied,
            'decks' => $userDecks,
            'languages' => $languages,
            'selectedDeckId' => $selectedDeckId,
            'selectedLanguageId' => $selectedLanguageId,
            'activeReviewSession' => $activeReviewSession,
            'exercises' => $exercises,
            'presets' => $presets,
            'defaultCount' => min(10, max(1, $totalDue)),
        ]);
    }

    /**
     * Bắt đầu phiên ôn tập.
     */
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'deck_ids' => ['nullable', 'array'],
            'deck_ids.*' => ['integer', 'exists:decks,id'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'is_extra_practice' => ['nullable', 'boolean'],
            'exercise_types' => ['nullable', 'array'],
            'exercise_types.*' => ['string'],
        ]);

        $session = $this->createReviewAction->execute(
            user: $user,
            deckIds: $validated['deck_ids'] ?? null,
            languageId: $validated['language_id'] ?? null,
            requestedCount: (int) $validated['count'],
            isExtraPractice: (bool) ($validated['is_extra_practice'] ?? false),
            selectedExerciseTypes: $validated['exercise_types'] ?? []
        );

        if (! $session) {
            return redirect()
                ->route('study.review.index')
                ->with('error', 'Không tìm thấy nghĩa từ vựng nào phù hợp với bộ lọc để ôn tập.');
        }

        return redirect()->route('study.session.show', $session);
    }
}
