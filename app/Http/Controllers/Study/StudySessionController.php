<?php

namespace App\Http\Controllers\Study;

use App\Actions\Study\RecordFamiliarizationAction;
use App\Actions\Study\RecordPracticeAction;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Http\Controllers\Controller;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Services\Study\QuestionGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudySessionController extends Controller
{
    public function __construct(
        protected RecordFamiliarizationAction $familiarizeAction,
        protected RecordPracticeAction $practiceAction,
        protected QuestionGeneratorService $questionService
    ) {}

    /**
     * Hiển thị giao diện màn hình học tập.
     */
    public function show(Request $request, StudySession $session): View|RedirectResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($session->status === StudySessionStatus::Completed) {
            return redirect()->route('study.session.complete', $session);
        }

        if ($session->status === StudySessionStatus::Abandoned) {
            return redirect()->route('study.new.prepare', $session->deck);
        }

        $session->load([
            'deck.learningLanguage',
            'deck.meaningLanguage',
            'items.entryMeaning.vocabularyEntry.meanings',
            'items.entryMeaning.examples',
        ]);

        $isRetrying = false;
        $previousItem = null;

        if ($session->mode === \App\Enums\StudyMode::LearnNew) {
            // Luồng Học từ mới: Chỉ làm quen, không tự chuyển sang luyện nhớ
            $requestedItemId = $request->query('item_id');
            if ($requestedItemId) {
                $currentItem = $session->items->firstWhere('id', (int) $requestedItemId);
            } else {
                $currentItem = $session->items->sortBy('sort_order')->firstWhere('familiarized_at', null);
            }

            if (! $currentItem) {
                // Toàn bộ đã làm quen xong
                $session->update(['status' => StudySessionStatus::Completed]);
                return redirect()->route('study.session.complete', $session);
            }

            // Tìm thẻ trước đó để có thể quay lại
            $allSortedItems = $session->items->sortBy('sort_order')->values();
            $currentIndex = $allSortedItems->search(fn ($it) => $it->id === $currentItem->id);
            if ($currentIndex !== false && $currentIndex > 0) {
                $previousItem = $allSortedItems->get($currentIndex - 1);
            }
        } else {
            // Luồng Luyện tập (Practice) & Ôn tập (Review): Vào thẳng luyện nhớ
            $currentItem = $session->items->sortBy('sort_order')->firstWhere('practiced_at', null);

            if (! $currentItem) {
                // Kiểm tra hàng đợi thử lại câu sai (retry_queue)
                $settings = $session->settings ?: [];
                $activeRetryId = $settings['active_retry_item_id'] ?? null;
                $retryQueue = $settings['retry_queue'] ?? [];

                if ($activeRetryId && $session->items->find($activeRetryId)) {
                    $currentItem = $session->items->find($activeRetryId);
                    $isRetrying = true;
                } elseif (! empty($retryQueue)) {
                    $nextRetryId = array_shift($retryQueue);
                    $settings['active_retry_item_id'] = $nextRetryId;
                    $settings['retry_queue'] = $retryQueue;
                    $session->settings = $settings;
                    $session->save();
                    $currentItem = $session->items->find($nextRetryId);
                    $isRetrying = true;
                }
            }

            if (! $currentItem) {
                $session->update(['status' => StudySessionStatus::Completed]);
                return redirect()->route('study.session.complete', $session);
            }
        }

        // Tính toán tiến độ
        $totalItemsCount = $session->items->count();
        $chunkSize = $session->chunk_size ?: $totalItemsCount;
        $currentChunkIndex = $session->current_chunk;
        $totalChunks = 1;

        // Tiến độ số từ đã làm quen / luyện nhớ
        $familiarizedCount = $session->items->whereNotNull('familiarized_at')->count();
        $practicedCount = $session->items->whereNotNull('practiced_at')->count();

        // Chuẩn bị dữ liệu cho bài luyện nhớ nếu không phải luồng Làm quen
        $practiceQuestion = null;
        if ($session->mode !== \App\Enums\StudyMode::LearnNew) {
            $practiceQuestion = $this->preparePracticeQuestion($session, $currentItem);
        }

        return view('study.session', [
            'session' => $session,
            'deck' => $session->deck,
            'currentItem' => $currentItem,
            'currentMeaning' => $currentItem->entryMeaning,
            'currentEntry' => $currentItem->entryMeaning->vocabularyEntry,
            'totalItemsCount' => $totalItemsCount,
            'currentChunkIndex' => $currentChunkIndex,
            'totalChunks' => $totalChunks,
            'familiarizedCount' => $familiarizedCount,
            'practicedCount' => $practicedCount,
            'practiceQuestion' => $practiceQuestion,
            'previousItem' => $previousItem,
            'isRetrying' => $isRetrying,
        ]);
    }

    /**
     * API ghi nhận làm quen một nghĩa.
     */
    public function familiarize(Request $request, StudySession $session, StudySessionItem $item): JsonResponse
    {
        if ($session->user_id !== $request->user()->id || $item->study_session_id !== $session->id) {
            abort(403);
        }

        $validated = $request->validate([
            'is_difficult' => ['nullable', 'boolean'],
            'is_self_declared_known' => ['nullable', 'boolean'],
        ]);

        $result = $this->familiarizeAction->execute(
            item: $item,
            isDifficult: (bool) ($validated['is_difficult'] ?? false),
            isSelfDeclaredKnown: (bool) ($validated['is_self_declared_known'] ?? false)
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * API ghi nhận kết quả luyện nhớ một nghĩa.
     */
    public function practice(Request $request, StudySession $session, StudySessionItem $item): JsonResponse
    {
        if ($session->user_id !== $request->user()->id || $item->study_session_id !== $session->id) {
            abort(403);
        }

        $validated = $request->validate([
            'result' => ['required', 'string', 'in:pass,fail,skip,self_remember,self_forgot'],
            'used_hint' => ['nullable', 'boolean'],
        ]);

        $result = $this->practiceAction->execute(
            item: $item,
            result: $validated['result'],
            usedHint: (bool) ($validated['used_hint'] ?? false)
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * API kiểm tra một cặp ghép trong bài Ghép cặp theo thời gian thực.
     */
    public function checkPair(Request $request, StudySession $session): JsonResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'term_id' => ['required'],
            'meaning_id' => ['required'],
        ]);

        $isCorrect = (string) $validated['term_id'] === (string) $validated['meaning_id'];

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
        ]);
    }

    /**
     * API cấp và ghi nhận gợi ý từ server.
     */
    public function hint(Request $request, StudySession $session, StudySessionItem $item): JsonResponse
    {
        if ($session->user_id !== $request->user()->id || $item->study_session_id !== $session->id) {
            abort(403);
        }

        $meaning = $item->entryMeaning;
        $term = $meaning->vocabularyEntry->term;
        $example = $meaning->examples->first();

        // Ghi nhận server đã cấp gợi ý cho mục này
        $settings = $session->settings ?: [];
        $hintedItems = $settings['hinted_items'] ?? [];
        if (! in_array($item->id, $hintedItems, true)) {
            $hintedItems[] = $item->id;
            $settings['hinted_items'] = $hintedItems;
            $session->settings = $settings;
            $session->save();
        }

        $firstLetter = mb_substr($term, 0, 1, 'UTF-8');
        $hintText = "Ký tự đầu tiên: \"{$firstLetter}\"";
        if ($example && $example->sentence) {
            $hintText .= " • Ngữ cảnh ví dụ: " . $example->sentence;
        }

        return response()->json([
            'success' => true,
            'hint' => $hintText,
        ]);
    }

    /**
     * API bật/tắt đánh dấu khó cho mục hiện tại.
     */
    public function toggleDifficult(Request $request, StudySession $session, StudySessionItem $item): JsonResponse
    {
        if ($session->user_id !== $request->user()->id || $item->study_session_id !== $session->id) {
            abort(403);
        }

        $newDifficult = ! $item->is_difficult;
        $item->update(['is_difficult' => $newDifficult]);

        // Cập nhật StudyRecord
        $record = \App\Models\StudyRecord::firstOrNew([
            'user_id' => $request->user()->id,
            'entry_meaning_id' => $item->entry_meaning_id,
        ]);
        if (! $record->exists) {
            $record->deck_id = $session->deck_id;
        }
        $record->is_difficult = $newDifficult;
        $record->save();

        return response()->json([
            'success' => true,
            'is_difficult' => $newDifficult,
        ]);
    }

    /**
     * API phục vụ câu kiểm tra nhanh khi người học bấm "Tôi đã biết".
     */
    public function quickVerify(Request $request, StudySession $session, StudySessionItem $item): JsonResponse
    {
        if ($session->user_id !== $request->user()->id || $item->study_session_id !== $session->id) {
            abort(403);
        }

        $userAnswer = $request->input('answer');
        $meaning = $item->entryMeaning;
        $correctTerm = $meaning->vocabularyEntry->term;

        if ($userAnswer === null) {
            // Chuẩn bị câu hỏi kiểm tra nhanh (trắc nghiệm)
            $siblings = \App\Models\EntryMeaning::whereHas('vocabularyEntry', fn ($q) => $q->where('deck_id', $session->deck_id))
                ->where('id', '!=', $meaning->id)
                ->inRandomOrder()
                ->take(3)
                ->get();

            $options = [
                ['id' => $correctTerm, 'text' => $correctTerm],
            ];
            foreach ($siblings as $sib) {
                $options[] = ['id' => $sib->vocabularyEntry->term, 'text' => $sib->vocabularyEntry->term];
            }
            shuffle($options);

            return response()->json([
                'success' => true,
                'question' => [
                    'prompt' => 'Kiểm tra nhanh: Từ vựng nào mang nghĩa "' . $meaning->meaning . '"?',
                    'options' => $options,
                ],
            ]);
        }

        // Chấm kiểm tra nhanh
        $normalizedAnswer = $this->questionService->normalizeText((string) $userAnswer);
        $normalizedCorrect = $this->questionService->normalizeText($correctTerm);
        $isCorrect = ($normalizedAnswer === $normalizedCorrect);

        if ($isCorrect) {
            // Tự khai báo "Tôi đã biết" được kiểm chứng thành công -> cấp lịch thử thách ngắn 2 ngày
            $this->familiarizeAction->execute($item, isDifficult: false, isSelfDeclaredKnown: true);
            return response()->json([
                'success' => true,
                'verified' => true,
                'feedback' => 'Xác nhận thành công! Nghĩa này được ghi nhận "Tôi đã biết" và xếp lịch ôn thử thách sau 2 ngày.',
            ]);
        }

        return response()->json([
            'success' => true,
            'verified' => false,
            'feedback' => 'Chưa chính xác! Bạn sẽ tiếp tục làm quen và luyện nhớ nghĩa này cùng nhóm.',
        ]);
    }

    /**
     * Tạm dừng phiên học và lưu trạng thái.
     */
    public function pause(Request $request, StudySession $session): RedirectResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $session->update([
            'status' => StudySessionStatus::Paused,
            'last_interacted_at' => now(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', "Phiên học bộ từ \"{$session->deck->name}\" đã được tạm dừng. Bạn có thể tiếp tục bất cứ lúc nào từ Bảng điều khiển.");
    }

    /**
     * Màn hình hoàn thành phiên học.
     */
    public function complete(Request $request, StudySession $session): View
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $session->load(['deck.learningLanguage', 'deck.meaningLanguage', 'items.entryMeaning.vocabularyEntry.meanings']);

        $totalItems = $session->items->count();
        $difficultCount = $session->items->where('is_difficult', true)->count();
        $selfKnownCount = $session->items->where('is_self_declared_known', true)->count();
        $passedPracticeCount = $session->items->where('practice_result', 'pass')->count();
        $failedPracticeCount = $session->items->where('practice_result', 'fail')->count();
        $skippedCount = $session->items->where('practice_result', 'skip')->count();
        $selfRememberCount = $session->items->where('practice_result', 'self_remember')->count();
        $selfForgotCount = $session->items->where('practice_result', 'self_forgot')->count();

        // Số mục đã dùng gợi ý
        $hintedItemIds = $session->settings['hinted_items'] ?? [];
        $hintedCount = count($hintedItemIds);

        // Danh sách các nghĩa cần luyện thêm (kết quả fail hoặc thử lại nhiều lần)
        $needsExtraPracticeItems = $session->items->filter(function ($item) {
            return in_array($item->practice_result, ['fail', 'self_forgot'], true);
        });

        // Lịch SRS có cập nhật không
        $srsUpdated = $session->mode->isSrsModifying() && ! ($session->settings['is_extra_practice'] ?? false);

        // Số nghĩa còn đến hạn ôn tập của bộ từ này
        $progressService = app(\App\Services\Study\StudyProgressService::class);
        $remainingDueCount = $progressService->getDueMeaningsCount($request->user(), $session->deck);

        // Thời gian học ước tính (phút)
        $start = $session->created_at ?? now();
        $end = $session->last_interacted_at ?? now();
        $activeMinutes = max(1, min(120, (int) round($end->diffInMinutes($start))));

        // Điểm đánh giá lần làm đầu tiên (không tính lần thử lại)
        $firstAttemptItems = $session->items->whereNotNull('first_attempt_result');
        $firstAttemptTotal = $firstAttemptItems->count();
        $firstAttemptPass = $firstAttemptItems->whereIn('first_attempt_result', ['pass', 'self_remember'])->count();
        $accuracyPercent = $firstAttemptTotal > 0 ? (int) round(($firstAttemptPass / $firstAttemptTotal) * 100) : 100;
        $retriesCount = count($session->settings['retries_count'] ?? []);

        return view('study.complete', [
            'session' => $session,
            'deck' => $session->deck,
            'totalItems' => $totalItems,
            'difficultCount' => $difficultCount,
            'selfKnownCount' => $selfKnownCount,
            'passedPracticeCount' => $passedPracticeCount,
            'failedPracticeCount' => $failedPracticeCount,
            'skippedCount' => $skippedCount,
            'selfRememberCount' => $selfRememberCount,
            'selfForgotCount' => $selfForgotCount,
            'firstAttemptTotal' => $firstAttemptTotal,
            'firstAttemptPass' => $firstAttemptPass,
            'accuracyPercent' => $accuracyPercent,
            'retriesCount' => $retriesCount,
            'hintedCount' => $hintedCount,
            'needsExtraPracticeItems' => $needsExtraPracticeItems,
            'srsUpdated' => $srsUpdated,
            'remainingDueCount' => $remainingDueCount,
            'activeMinutes' => $activeMinutes,
        ]);
    }

    /**
     * Chuẩn bị câu hỏi luyện nhớ cho mục hiện tại (tái sử dụng QuestionGeneratorService cho 8 dạng bài).
     */
    protected function preparePracticeQuestion(StudySession $session, StudySessionItem $item): array
    {
        $meaning = $item->entryMeaning;
        $deck = $session->deck;

        $exerciseTypes = $session->settings['exercise_types'] ?? [];
        if (empty($exerciseTypes)) {
            $exerciseType = 'multiple_choice_term_to_meaning';
        } else {
            $exerciseType = $exerciseTypes[$item->sort_order % count($exerciseTypes)];
        }

        return $this->questionService->generateQuestion($meaning, $exerciseType, $deck);
    }
}

