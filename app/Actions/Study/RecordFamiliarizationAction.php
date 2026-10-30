<?php

namespace App\Actions\Study;

use App\Enums\StudyStage;
use App\Models\StudyRecord;
use App\Models\StudySessionItem;
use Illuminate\Support\Facades\DB;

class RecordFamiliarizationAction
{
    /**
     * Ghi nhận người học đã làm quen một nghĩa.
     * Cập nhật tiến độ Spaced Repetition an toàn, không cấp thời gian ôn dài khi tự đánh giá.
     */
    public function execute(
        StudySessionItem $item,
        bool $isDifficult = false,
        bool $isSelfDeclaredKnown = false
    ): array {
        $session = $item->session;
        $user = $session->user;
        $meaning = $item->entryMeaning;

        return DB::transaction(function () use ($item, $session, $user, $meaning, $isDifficult, $isSelfDeclaredKnown) {
            $item->update([
                'familiarized_at' => now(),
                'is_difficult' => $isDifficult,
                'is_self_declared_known' => $isSelfDeclaredKnown,
            ]);

            // Cập nhật hoặc khởi tạo StudyRecord cho nghĩa này
            $record = StudyRecord::firstOrNew([
                'user_id' => $user->id,
                'entry_meaning_id' => $meaning->id,
            ]);

            $record->deck_id = $session->deck_id;
            $record->familiarized_at = $record->familiarized_at ?: now();
            $record->is_difficult = $isDifficult;
            $record->is_self_declared_known = $isSelfDeclaredKnown;
            $record->last_studied_at = now();

            if ($isSelfDeclaredKnown) {
                // Người học tự khai báo "Tôi đã biết nghĩa này":
                // Ghi nhận tự khai báo, cấp lịch ôn thử thách ngắn (ví dụ: 2 ngày),
                // tuyệt đối không tự động đánh dấu thành thạo lâu dài.
                $interval = (int) config('polyvocab.study.self_known_initial_interval_days', 2);
                $record->repetition_level = 1;
                $record->interval_days = $interval;
                $record->due_at = now()->addDays($interval);
            } else {
                $interval = (int) config('polyvocab.study.initial_interval_days', 1);
                $record->repetition_level = 0;
                $record->interval_days = $interval;
                $record->due_at = now()->addDays($interval);
            }

            $record->save();

            // Kiểm tra xem toàn bộ các mục trong phiên đã làm quen xong chưa
            $allItems = $session->items()->get();
            $allFamiliarized = $allItems->every(fn ($it) => $it->familiarized_at !== null);

            $sessionCompleted = false;
            if ($allFamiliarized) {
                // Hoàn thành phiên làm quen từ mới
                $session->update([
                    'status' => \App\Enums\StudySessionStatus::Completed,
                    'last_interacted_at' => now(),
                ]);
                $sessionCompleted = true;
            } else {
                $session->update([
                    'last_interacted_at' => now(),
                ]);
            }

            return [
                'item_id' => $item->id,
                'familiarized_at' => $item->familiarized_at,
                'session_completed' => $sessionCompleted,
                'session_stage' => $session->stage->value,
            ];
        });
    }
}

