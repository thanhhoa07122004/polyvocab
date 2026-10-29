<?php

namespace App\Services\Study;

use App\Models\StudyRecord;
use Carbon\Carbon;

class ReviewSchedulePolicy
{
    /**
     * Phiên bản chính sách ôn tập.
     */
    public const VERSION = 'v1';

    /**
     * Lấy danh sách các bước giãn cách (ngày) từ cấu hình.
     *
     * @return list<int>
     */
    public function getIntervalSteps(): array
    {
        return config('polyvocab.study.review_policy.interval_steps', [1, 3, 7, 14, 30]);
    }

    /**
     * Tính toán và cập nhật lịch ôn tập cho một nghĩa trong phiên ôn.
     *
     * Quy tắc cập nhật:
     * 1. Lần trả lời có chấm đầu tiên đúng, không gợi ý và không bỏ qua trước đó:
     *    Tăng tối đa 1 bước (repetition_level), đặt hạn theo bước mới.
     * 2. Sai, bỏ qua hoặc cần gợi ý:
     *    Đưa về bước đầu (bước 0, tương ứng 1 ngày khi ôn lại) và đặt hạn ôn lại ngắn (10 phút).
     * 3. Chỉ tự đánh giá "Nhớ" (Flashcard):
     *    Giữ nguyên bước hiện tại, đặt hạn ôn lại sau 1 ngày.
     * 4. Tự đánh giá "Chưa nhớ":
     *    Đưa về bước đầu và ôn lại sau 10 phút.
     * 5. Thời điểm tính hạn dựa trên thời điểm server ghi nhận request (recorded_at).
     * 6. Không tự động gán trạng thái thành thạo vĩnh viễn dù đạt bước cuối.
     *
     * @param StudyRecord $record Bản ghi SRS của nghĩa
     * @param string $result 'pass', 'fail', 'skip', 'self_remember', 'self_forgot'
     * @param bool $usedHint Người dùng có sử dụng gợi ý hay không
     * @param bool $isFirstAttempt Đây có phải là lần trả lời đầu tiên trong phiên ôn hay không
     * @param Carbon|null $recordedAt Thời điểm ghi nhận từ server
     * @return array<string, mixed> Kết quả tính toán
     */
    public function calculateScheduleUpdate(
        StudyRecord $record,
        string $result,
        bool $usedHint = false,
        bool $isFirstAttempt = true,
        ?Carbon $recordedAt = null
    ): array {
        $now = $recordedAt ?: now();
        $steps = $this->getIntervalSteps();
        $maxStep = count($steps) - 1;

        $currentLevel = (int) $record->repetition_level;
        $lapseMinutes = (int) config('polyvocab.study.review_policy.lapse_interval_minutes', 10);
        $selfRememberDays = (int) config('polyvocab.study.review_policy.self_remember_interval_days', 1);

        $newLevel = $currentLevel;
        $intervalDays = 0;
        $dueAt = $now->copy();
        $isLapse = false;

        if ($result === 'self_remember') {
            // Tự đánh giá "Nhớ": Giữ bước hiện tại, ôn lại sau 1 ngày
            $intervalDays = $selfRememberDays;
            $dueAt = $now->copy()->addDays($selfRememberDays);
        } elseif ($result === 'self_forgot') {
            // Tự đánh giá "Chưa nhớ": Đưa về bước đầu, ôn lại sau 10 phút
            $newLevel = 0;
            $intervalDays = 0;
            $dueAt = $now->copy()->addMinutes($lapseMinutes);
            $isLapse = true;
        } elseif ($result === 'pass') {
            if ($isFirstAttempt && ! $usedHint) {
                // Lần trả lời có chấm đầu tiên đúng, không gợi ý, không bỏ qua:
                // Tăng tối đa 1 bước
                $newLevel = min($maxStep, $currentLevel + 1);
                $intervalDays = $steps[$newLevel] ?? 1;
                $dueAt = $now->copy()->addDays($intervalDays);
            } else {
                // Luyện lại đúng trong cùng phiên hoặc đúng sau khi đã xem gợi ý:
                // Không tăng bước, giữ hạn bước hiện tại hoặc ôn lại thử thách ngắn
                $intervalDays = $steps[$currentLevel] ?? 1;
                $dueAt = $now->copy()->addDays($intervalDays);
            }
        } else {
            // Sai ('fail') hoặc bỏ qua ('skip') hoặc cần gợi ý:
            // Đưa về bước đầu và ôn lại sau 10 phút
            $newLevel = 0;
            $intervalDays = 0;
            $dueAt = $now->copy()->addMinutes($lapseMinutes);
            $isLapse = true;
        }

        return [
            'previous_level' => $currentLevel,
            'new_level' => $newLevel,
            'interval_days' => $intervalDays,
            'due_at' => $dueAt,
            'is_lapse' => $isLapse,
            'policy_version' => self::VERSION,
        ];
    }

    /**
     * Áp dụng cập nhật lịch ôn vào StudyRecord.
     * Lưu ý: Trong một phiên ôn, mỗi nghĩa chỉ finalize lịch một lần duy nhất.
     */
    public function applyScheduleUpdate(
        StudyRecord $record,
        string $result,
        bool $usedHint = false,
        bool $isFirstAttempt = true,
        ?Carbon $recordedAt = null
    ): StudyRecord {
        $calc = $this->calculateScheduleUpdate($record, $result, $usedHint, $isFirstAttempt, $recordedAt);

        $record->repetition_level = $calc['new_level'];
        $record->interval_days = $calc['interval_days'];
        $record->due_at = $calc['due_at'];
        $record->last_studied_at = $recordedAt ?: now();

        if ($calc['is_lapse']) {
            $record->lapse_count = ($record->lapse_count ?? 0) + 1;
        } else {
            $record->review_count = ($record->review_count ?? 0) + 1;
        }

        $record->save();

        return $record;
    }
}

