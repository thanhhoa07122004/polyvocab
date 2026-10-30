<?php

namespace App\Http\Controllers\Study;

use App\Actions\Study\CreateLearnNewSessionAction;
use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Models\StudySession;
use App\Services\Study\ExerciseEligibilityService;
use App\Services\Study\StudyProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearnNewController extends Controller
{
    public function __construct(
        protected StudyProgressService $progressService,
        protected ExerciseEligibilityService $eligibilityService,
        protected CreateLearnNewSessionAction $createSessionAction
    ) {}

    /**
     * Chọn bộ từ để bắt đầu học từ mới nếu đi từ nút chung trên Dashboard.
     */
    public function selectDeck(Request $request): View
    {
        $user = $request->user();
        $decks = $user->decks()
            ->with(['learningLanguage', 'meaningLanguage'])
            ->withCount('entries')
            ->latest('updated_at')
            ->get()
            ->map(function ($deck) use ($user) {
                $deck->unstudied_count = $this->progressService->getUnstudiedMeaningsCount($user, $deck);
                return $deck;
            });

        return view('study.select_deck', [
            'decks' => $decks,
            'mode' => StudyMode::LearnNew,
        ]);
    }

    /**
     * Màn hình chuẩn bị học từ mới cho một bộ từ cụ thể.
     */
    public function prepare(Request $request, Deck $deck): View
    {
        // Kiểm tra quyền xem bộ từ (trả về 404 nếu private để không lộ sự tồn tại)
        if (! Gate::allows('view', $deck)) {
            abort(404);
        }

        $user = $request->user();
        $availableMeaningsCount = $this->progressService->getUnstudiedMeaningsCount($user, $deck);
        $activeSession = $this->progressService->getActiveSession($user, $deck);
        $exercises = $this->eligibilityService->evaluateDeckExercises($deck);

        $presets = config('polyvocab.study.batch_presets', [5, 10, 20]);
        $defaultCount = config('polyvocab.study.default_new_meanings_count', 10);
        $maxBatch = config('polyvocab.study.max_batch_size', 50);
        $minBatch = config('polyvocab.study.min_batch_size', 1);

        return view('study.prepare_new', [
            'deck' => $deck->load(['learningLanguage', 'meaningLanguage']),
            'availableCount' => $availableMeaningsCount,
            'activeSession' => $activeSession,
            'exercises' => $exercises,
            'presets' => $presets,
            'defaultCount' => min($defaultCount, max($minBatch, $availableMeaningsCount)),
            'minBatch' => $minBatch,
            'maxBatch' => $maxBatch,
        ]);
    }

    /**
     * Khởi tạo hoặc tiếp tục phiên học từ mới.
     * Tránh tạo trùng lặp phiên khi người dùng tải lại trang.
     */
    public function start(Request $request, Deck $deck): RedirectResponse
    {
        if (! Gate::allows('view', $deck)) {
            abort(404);
        }

        $user = $request->user();
        $minBatch = (int) config('polyvocab.study.min_batch_size', 1);
        $maxBatch = (int) config('polyvocab.study.max_batch_size', 50);

        $validated = $request->validate([
            'count' => ['required', 'integer', "min:{$minBatch}", "max:{$maxBatch}"],
        ]);

        $session = $this->createSessionAction->execute(
            user: $user,
            deck: $deck,
            requestedCount: (int) $validated['count']
        );

        if (! $session) {
            return redirect()
                ->route('study.new.prepare', $deck)
                ->with('error', 'Bộ từ này hiện không còn từ nào chưa học.');
        }

        return redirect()->route('study.session.show', $session);
    }

    /**
     * Kết thúc phiên học cũ đang dang dở để có thể tạo phiên mới.
     * Giải thích rõ ràng cách ghi nhận phần đã học.
     */
    public function abandon(Request $request, StudySession $session): RedirectResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $deck = $session->deck;

        $session->update([
            'status' => StudySessionStatus::Abandoned,
            'last_interacted_at' => now(),
        ]);

        $redirectRoute = match ($session->mode) {
            \App\Enums\StudyMode::Review => route('study.review.index', ['deck_id' => $deck->id]),
            \App\Enums\StudyMode::PracticeCustom, \App\Enums\StudyMode::PracticeDifficult => route('study.practice.deck', $deck),
            default => route('study.new.prepare', $deck),
        };

        return redirect($redirectRoute)
            ->with('status', 'Đã kết thúc phiên dở dang. Các nghĩa bạn đã hoàn thành trước đó vẫn được lưu lại an toàn; các nghĩa chưa làm đã được trả lại hàng đợi.');
    }
}
