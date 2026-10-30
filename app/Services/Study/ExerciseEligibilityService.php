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
     * - eligible_count: int
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
        $meaningsWithExamples = EntryMeaning::whereHas('vocabularyEntry', function ($q) use ($deck) {
            $q->where('deck_id', $deck->id);
        })->has('examples')
        ->with(['vocabularyEntry:id,term', 'examples:id,entry_meaning_id,sentence'])
        ->get();

        $meaningsWithExamplesCount = $meaningsWithExamples->count();

        // Đếm số nghĩa có câu ví dụ thực sự chứa từ vựng (để tạo câu điền từ hợp lệ)
        $clozeEligibleCount = $meaningsWithExamples->filter(function ($meaning) {
            $term = $meaning->vocabularyEntry?->term;
            if (! $term) {
                return false;
            }
            foreach ($meaning->examples as $ex) {
                if ($ex->sentence && mb_stripos($ex->sentence, $term) !== false) {
                    return true;
                }
            }
            return false;
        })->count();

        // Hiện tại hệ thống chưa tích hợp tệp audio upload riêng
        $meaningsWithAudioCount = 0;

        $results = [];

        foreach ($exerciseTypes as $key => $type) {
            $isEligible = true;
            $reason = null;
            $eligibleCount = $totalMeaningsCount;

            // Lấy tên và mô tả từ file dịch nếu có
            $localizedName = trans("study.exercises.{$key}.name");
            if ($localizedName && $localizedName !== "study.exercises.{$key}.name") {
                $type['name'] = $localizedName;
            }
            $localizedDesc = trans("study.exercises.{$key}.desc");
            if ($localizedDesc && $localizedDesc !== "study.exercises.{$key}.desc") {
                $type['description'] = $localizedDesc;
            }

            // Kiểm tra yêu cầu audio
            if (! empty($type['requires_audio'])) {
                if ($meaningsWithAudioCount === 0) {
                    $isEligible = false;
                    $eligibleCount = 0;
                    $reason = trans('study.reasons.no_audio');
                }
            }

            // Kiểm tra yêu cầu câu ví dụ (dành riêng cho điền từ)
            if ($isEligible && ! empty($type['requires_examples'])) {
                if ($meaningsWithExamplesCount === 0) {
                    $isEligible = false;
                    $eligibleCount = 0;
                    $reason = trans('study.reasons.no_examples');
                } elseif ($clozeEligibleCount === 0) {
                    $isEligible = false;
                    $eligibleCount = 0;
                    $reason = trans('study.reasons.no_suitable_cloze');
                } else {
                    $eligibleCount = $clozeEligibleCount;
                }
            }

            // Kiểm tra số lượng mục tối thiểu trong bộ để tạo phương án trắc nghiệm / ghép cặp
            if ($isEligible) {
                if ($key === 'multiple_choice_meaning_to_term' && $distinctTermsCount < 2) {
                    $isEligible = false;
                    $reason = trans('study.reasons.need_min_terms', ['min' => 2]);
                } elseif ($key === 'multiple_choice_term_to_meaning' && $totalMeaningsCount < 2) {
                    $isEligible = false;
                    $reason = trans('study.reasons.need_min_meanings', ['min' => 2]);
                } elseif ($key === 'matching_pairs' && $totalMeaningsCount < 2) {
                    $isEligible = false;
                    $reason = trans('study.reasons.need_min_meanings', ['min' => 2]);
                } elseif ($key === 'listen_and_choose' && $totalMeaningsCount < 2) {
                    $isEligible = false;
                    $reason = trans('study.reasons.need_min_meanings', ['min' => 2]);
                } elseif ($totalMeaningsCount < ($type['min_deck_meanings'] ?? 1)) {
                    $isEligible = false;
                    $reason = trans('study.reasons.need_min_meanings', ['min' => $type['min_deck_meanings'] ?? 1]);
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
