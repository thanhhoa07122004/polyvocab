<?php

namespace App\Actions\Study;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Models\StudyRecord;
use App\Models\StudySessionItem;
use App\Services\Study\ReviewSchedulePolicy;
use Illuminate\Support\Facades\DB;

class RecordPracticeAction
{
    public function __construct(
        protected ReviewSchedulePolicy $reviewPolicy
    ) {}

    /**
     * Ghi nhận kết quả luyện nhớ/ôn tập một nghĩa và điều hướng tiến độ phiên.
     * Tuân thủ quy tắc SRS: mỗi nghĩa chỉ finalize lịch một lần duy nhất trong phiên ôn.
     */
    public function execute(
        StudySessionItem $item,
        string $result,
        bool $usedHint = false
    ): array {
        $session = $item->session;
        $user = $session->user;
        $meaning = $item->entryMeaning;

        return DB::transaction(function () use ($item, $session, $user, $meaning, $result, $usedHint) {
            $settings = $session->settings ?: [];
            $finalizedMeanings = $settings['finalized_meanings'] ?? [];
            $isExtraPractice = (bool) ($settings['is_extra_practice'] ?? false);

            $isFirstAttempt = ! in_array($meaning->id, $finalizedMeanings, true);

            $item->update([
                'practiced_at' => now(),
                'practice_result' => $result,
            ]);

            // Lấy hoặc tạo StudyRecord
            $record = StudyRecord::firstOrNew([
                'user_id' => $user->id,
                'entry_meaning_id' => $meaning->id,
            ]);

            if (! $record->exists) {
                $record->deck_id = $session->deck_id;
            }

            // Xử lý cập nhật SRS theo chế độ
            if ($session->mode === StudyMode::Review) {
                if (! $isExtraPractice && $isFirstAttempt) {
                    // Chỉ finalize lịch ôn một lần cho mỗi nghĩa trong mỗi phiên ôn chính thức
                    $this->reviewPolicy->applyScheduleUpdate(
                        record: $record,
                        result: $result,
                        usedHint: $usedHint,
                        isFirstAttempt: true,
                        recordedAt: now()
                    );
                    $finalizedMeanings[] = $meaning->id;
                    $settings['finalized_meanings'] = $finalizedMeanings;
                } else {
                    // Luyện lại trong cùng phiên hoặc chế độ Luyện thêm ngoài lịch:
                    // Mặc định không thay đổi lịch ôn SRS, chỉ ghi nhận thời điểm học
                    $record->last_studied_at = now();
                    $record->save();
                }
            } elseif ($session->mode === StudyMode::PracticeCustom || $session->mode === StudyMode::PracticeDifficult) {
                // Chế độ Luyện theo dạng bài hoặc Luyện từ khó:
                // Mặc định KHÔNG thay đổi lịch ôn SRS và KHÔNG sửa đổi chu kỳ lặp lại
                $record->last_studied_at = now();
                $record->save();
            } else {
                // Chế độ Học từ mới
                $record->last_studied_at = now();
                if ($result === 'pass') {
                    $record->review_count = ($record->review_count ?? 0) + 1;
                    $record->repetition_level = ($record->repetition_level ?? 0) + 1;
                    $record->interval_days = 2;
                    $record->due_at = now()->addDays(2);
                } elseif ($result === 'fail') {
                    $record->lapse_count = ($record->lapse_count ?? 0) + 1;
                    $record->repetition_level = 0;
                    $record->interval_days = 1;
                    $record->due_at = now()->addDay();
                }
                $record->save();
            }

            // Quản lý số lần thử lại câu sai
            $retriesCount = $settings['retries_count'] ?? [];
            $maxRetries = (int) config('polyvocab.study.review_policy.max_session_retries', 2);
            $needsExtraPractice = false;

            if ($result === 'fail') {
                $currentRetries = ($retriesCount[$item->id] ?? 0) + 1;
                $retriesCount[$item->id] = $currentRetries;
                if ($currentRetries >= $maxRetries) {
                    $needsExtraPractice = true;
                }
            }
            $settings['retries_count'] = $retriesCount;

            $session->settings = $settings;

            // Kiểm tra tiến độ hoàn thành nhóm hoặc phiên
            $currentChunkItems = $session->items()
                ->where('chunk_index', $session->current_chunk)
                ->get();

            $allChunkPracticed = $currentChunkItems->every(fn ($it) => $it->practiced_at !== null);

            $sessionCompleted = false;
            $nextChunkStarted = false;

            if ($allChunkPracticed) {
                $maxChunk = $session->items()->max('chunk_index');
                if ($session->current_chunk < $maxChunk) {
                    $session->current_chunk = $session->current_chunk + 1;
                    $session->stage = ($session->mode === StudyMode::Review)
                        ? StudyStage::RetentionPractice
                        : StudyStage::Familiarization;
                    $nextChunkStarted = true;
                } else {
                    $session->status = StudySessionStatus::Completed;
                    $sessionCompleted = true;
                }
            }

            $session->last_interacted_at = now();
            $session->save();

            return [
                'item_id' => $item->id,
                'practiced_at' => $item->practiced_at,
                'practice_result' => $result,
                'next_chunk_started' => $nextChunkStarted,
                'session_completed' => $sessionCompleted,
                'current_chunk' => $session->current_chunk,
                'stage' => $session->stage->value,
                'needs_extra_practice' => $needsExtraPractice,
            ];
        });
    }
}
