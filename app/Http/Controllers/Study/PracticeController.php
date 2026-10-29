<?php

namespace App\Http\Controllers\Study;

use App\Actions\Study\CreatePracticeSessionAction;
use App\Enums\StudyMode;
use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Models\Language;
use App\Services\Study\ExerciseEligibilityService;
use App\Services\Study\StudyProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PracticeController extends Controller
{
    public function __construct(
        protected StudyProgressService $progressService,
        protected ExerciseEligibilityService $eligibilityService,
        protected CreatePracticeSessionAction $createPracticeAction
    ) {}

    /**
     * Màn hình chuẩn bị luyện tập tổng quát (chọn 1 hoặc nhiều bộ từ).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $accessibleDecks = Deck::query()
            ->with(['learningLanguage', 'meaningLanguage'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('visibility', 'public');
            })
            ->withCount('entries')
            ->orderBy('name')
            ->get();

        $selectedDeckIds = $request->input('deck_ids', []);
        if (is_string($selectedDeckIds)) {
            $selectedDeckIds = explode(',', $selectedDeckIds);
        }
        $selectedDeckIds = array_map('intval', (array) $selectedDeckIds);

        if (empty($selectedDeckIds) && $accessibleDecks->isNotEmpty()) {
            $selectedDeckIds = [$accessibleDecks->first()->id];
        }

        $scope = $request->query('scope', 'all');
        if (! in_array($scope, ['all', 'studied', 'unstudied', 'difficult'], true)) {
            $scope = 'all';
        }

        // Lấy bộ từ đầu tiên để kiểm tra dạng bài mẫu
        $primaryDeck = $accessibleDecks->firstWhere('id', $selectedDeckIds[0] ?? null) ?? $accessibleDecks->first();
        $exerciseTypes = $primaryDeck ? $this->eligibilityService->evaluateDeckExercises($primaryDeck) : [];

        // Đếm số nghĩa khả dụng theo scope
        $availableMeaningsCount = 0;
        if (! empty($selectedDeckIds)) {
            $availableMeaningsCount = $this->progressService
                ->getEligibleMeaningsForPractice($user, $selectedDeckIds, $scope, $scope === 'difficult')
                ->count();
        }

        // Kiểm tra xem có phiên luyện dở không
        $activeSession = $this->progressService->getActiveSession($user, $primaryDeck);

        return view('study.practice_index', [
            'accessibleDecks' => $accessibleDecks,
            'selectedDeckIds' => $selectedDeckIds,
            'scope' => $scope,
            'exerciseTypes' => $exerciseTypes,
            'availableMeaningsCount' => $availableMeaningsCount,
            'activeSession' => $activeSession,
            'primaryDeck' => $primaryDeck,
        ]);
    }

    /**
     * Màn hình chuẩn bị luyện tập cho một bộ từ cụ thể (từ trang chi tiết bộ từ).
     */
    public function prepareDeck(Request $request, Deck $deck): View
    {
        if ($deck->isPrivate() && $request->user()->id !== $deck->user_id) {
            abort(404);
        }

        $user = $request->user();
        $scope = $request->query('scope', 'all');
        if (! in_array($scope, ['all', 'studied', 'unstudied', 'difficult'], true)) {
            $scope = 'all';
        }

        $deck->load(['learningLanguage', 'meaningLanguage']);
        $exerciseTypes = $this->eligibilityService->evaluateDeckExercises($deck);

        $availableMeaningsCount = $this->progressService
            ->getEligibleMeaningsForPractice($user, [$deck->id], $scope, $scope === 'difficult')
            ->count();

        $activeSession = $this->progressService->getActiveSession($user, $deck);

        return view('study.practice_prepare', [
            'deck' => $deck,
            'scope' => $scope,
            'exerciseTypes' => $exerciseTypes,
            'availableMeaningsCount' => $availableMeaningsCount,
            'activeSession' => $activeSession,
        ]);
    }

    /**
     * Bắt đầu phiên luyện tập tự do.
     */
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'deck_ids' => ['required', 'array', 'min:1'],
            'deck_ids.*' => ['integer', 'exists:decks,id'],
            'scope' => ['required', 'string', 'in:all,studied,unstudied,difficult'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'exercise_types' => ['nullable', 'array'],
            'exercise_types.*' => ['string'],
        ], [
            'deck_ids.required' => 'Vui lòng chọn ít nhất một bộ từ.',
            'scope.required' => 'Vui lòng chọn phạm vi nội dung.',
            'count.min' => 'Số lượng nghĩa tối thiểu là 1.',
            'count.max' => 'Số lượng nghĩa tối đa là 50.',
        ]);

        $selectedExerciseTypes = $validated['exercise_types'] ?? ['flashcard', 'multiple_choice_term_to_meaning'];

        $session = $this->createPracticeAction->execute(
            user: $user,
            deckIds: $validated['deck_ids'],
            scope: $validated['scope'],
            requestedCount: (int) $validated['count'],
            selectedExerciseTypes: $selectedExerciseTypes,
            isDifficultOnly: ($validated['scope'] === 'difficult')
        );

        if (! $session) {
            return back()->withInput()->withErrors([
                'count' => 'Không tìm thấy nghĩa nào phù hợp trong phạm vi đã chọn để bắt đầu luyện tập.',
            ]);
        }

        return redirect()->route('study.session.show', $session);
    }
}

