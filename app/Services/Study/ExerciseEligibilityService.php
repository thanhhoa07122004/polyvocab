<?php

namespace App\Services\Study;

use App\Models\Deck;
use App\Models\EntryMeaning;

class ExerciseEligibilityService
{
    /**
     * Đánh giá điều kiện khả dụng của tất cả 8 dạng bài học đối với một bộ từ.
     * Trả về danh sách dạng bài kèm:
     * - id, name, description, example
     * - is_eligible: boolean
     * - eligible_meanings_count: int
     * - disabled_reason: string|null
     *
     * @return array<string, array<string, mixed>>
     */
    public function evaluateDeckExercises(Deck $deck): array
    {
        $exerciseTypes = config('polyvocab.study.exercise_types', []);

        // Tổng số nghĩa trong bộ từ
        $totalMeaningsCount = EntryMeaning::whereHas('vocabularyEntry', function ($q) use ($deck) {
            $q->where('deck_id', $deck->id);
        })->count();

        // Tổng số từ vựng (term) riêng biệt trong bộ từ
        $distinctTermsCount = $deck->entries()->distinct('term')->count('term');

        // Số nghĩa có ít nhất một câu ví dụ
        $meaningsWithExamplesCount = EntryMeaning::whereHas('vocabularyEntry', function ($q) use ($deck) {
            $q->where('deck_id', $deck->id);
        })->has('examples')->count();

        // Hiện tại hệ thống chưa tích hợp tệp audio upload riêng
        $meaningsWithAudioCount = 0;

        $results = [];

        foreach ($exerciseTypes as $key => $type) {
            $isEligible = true;
            $reason = null;
            $eligibleCount = $totalMeaningsCount;

            // Kiểm tra yêu cầu audio
            if (! empty($type['requires_audio'])) {
                if ($meaningsWithAudioCount === 0) {
                    $isEligible = false;
                    $eligibleCount = 0;
                    $reason = 'Chưa có tệp âm thanh thu âm hoặc dịch vụ phát âm cho bộ từ này.';
                }
            }

            // Kiểm tra yêu cầu câu ví dụ
            if ($isEligible && ! empty($type['requires_examples'])) {
                $eligibleCount = $meaningsWithExamplesCount;
                if ($meaningsWithExamplesCount === 0) {
                    $isEligible = false;
                    $reason = 'Bộ từ chưa có câu ví dụ nào. Cần thêm ví dụ cho các nghĩa để kích hoạt dạng bài này.';
                }
            }

            // Kiểm tra số lượng mục tối thiểu trong bộ để tạo phương án trắc nghiệm / ghép cặp
            if ($isEligible) {
                if ($key === 'multiple_choice_meaning_to_term' && $distinctTermsCount < 2) {
                    $isEligible = false;
                    $reason = 'Cần tối thiểu 2 từ vựng khác nhau trong bộ để tạo các phương án lựa chọn.';
                } elseif ($key === 'multiple_choice_term_to_meaning' && $totalMeaningsCount < 2) {
                    $isEligible = false;
                    $reason = 'Cần tối thiểu 2 nghĩa khác nhau trong bộ để tạo các phương án trắc nghiệm.';
                } elseif ($key === 'matching_pairs' && $totalMeaningsCount < 2) {
                    $isEligible = false;
                    $reason = 'Cần tối thiểu 2 nghĩa trong bộ để có thể tạo bài tập ghép cặp.';
                } elseif ($key === 'listen_and_choose' && $totalMeaningsCount < 2) {
                    $isEligible = false;
                    $reason = 'Cần tối thiểu 2 nghĩa khác nhau trong bộ để tạo bài tập nghe và chọn.';
                } elseif ($totalMeaningsCount < ($type['min_deck_meanings'] ?? 1)) {
                    $isEligible = false;
                    $reason = 'Số lượng từ vựng trong bộ chưa đáp ứng yêu cầu tối thiểu của dạng bài này.';
                }
            }

            $results[$key] = array_merge($type, [
                'is_eligible' => $isEligible,
                'eligible_count' => $eligibleCount,
                'disabled_reason' => $reason,
            ]);
        }

        return $results;
    }
}

