<?php

namespace App\Services\Study;

use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\VocabularyEntry;

class ExerciseEligibilityService
{
    /**
     * Đánh giá điều kiện khả dụng của tất cả 8 dạng bài học đối với một bộ từ.
     *
     * @return array<string, array<string, mixed>>
     */
    public function evaluateDeckExercises(Deck $deck): array
    {
        return $this->evaluateDecksExercises([$deck->id]);
    }

    /**
     * Đánh giá điều kiện khả dụng của tất cả 8 dạng bài học đối với một hoặc nhiều bộ từ.
     *
     * @param array<int> $deckIds
     * @return array<string, array<string, mixed>>
     */
    public function evaluateDecksExercises(array $deckIds): array
    {
        $exerciseTypes = config('polyvocab.study.exercise_types', []);

        if (empty($deckIds)) {
            $results = [];
            foreach ($exerciseTypes as $key => $type) {
                $localizedName = trans("study.exercises.{$key}.name");
                if ($localizedName && $localizedName !== "study.exercises.{$key}.name") {
                    $type['name'] = $localizedName;
                }
                $localizedDesc = trans("study.exercises.{$key}.desc");
                if ($localizedDesc && $localizedDesc !== "study.exercises.{$key}.desc") {
                    $type['description'] = $localizedDesc;
                }

                $results[$key] = array_merge($type, [
                    'is_eligible' => false,
                    'eligible_count' => 0,
                    'disabled_reason' => 'Vui lòng chọn ít nhất một bộ từ.',
                ]);
            }
            return $results;
        }

        // Tổng số nghĩa trong các bộ từ đã chọn
        $totalMeaningsCount = EntryMeaning::whereHas('vocabularyEntry', function ($q) use ($deckIds) {
            $q->whereIn('deck_id', $deckIds);
        })->count();

        // Tổng số từ vựng (term) riêng biệt trong các bộ từ đã chọn
        $distinctTermsCount = VocabularyEntry::whereIn('deck_id', $deckIds)
            ->distinct('term')
            ->count('term');

        // Số nghĩa có ít nhất một câu ví dụ trong các bộ từ đã chọn
        $meaningsWithExamples = EntryMeaning::whereHas('vocabularyEntry', function ($q) use ($deckIds) {
            $q->whereIn('deck_id', $deckIds);
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

        // Số nghĩa có từ vựng đã có tệp audio hợp lệ
        $meaningsWithAudioCount = EntryMeaning::whereHas('vocabularyEntry', function ($q) use ($deckIds) {
            $q->whereIn('deck_id', $deckIds)
                ->whereNotNull('audio_path');
        })->count();

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
            // Hệ thống hỗ trợ phát âm bằng tệp chất lượng cao Azure TTS (nếu có)
            // hoặc tự động phát âm bằng Web Speech API của trình duyệt.
            // Do đó người học luôn có thể chọn luyện tập các dạng bài nghe!
            if (! empty($type['requires_audio'])) {
                $type['has_server_audio'] = ($meaningsWithAudioCount > 0);
                $type['server_audio_count'] = $meaningsWithAudioCount;
                $eligibleCount = $totalMeaningsCount;
            }

            // Kiểm tra yêu cầu câu ví dụ (dành riêng cho điền từ)
            if ($isEligible && ! empty($type['requires_examples'])) {
                $aiService = app(\App\Services\Ai\AiSentenceService::class);
                $isAiConfigured = $aiService->isConfigured();
                $type['is_ai_configured'] = $isAiConfigured;

                if ($meaningsWithExamplesCount === 0 || $clozeEligibleCount === 0) {
                    if ($isAiConfigured) {
                        // Khi đã cấu hình AI, dạng bài Điền từ có thể sử dụng AI để đặt câu tự động
                        $type['can_use_ai_cloze'] = true;
                        $eligibleCount = $totalMeaningsCount;
                    } else {
                        $isEligible = false;
                        $eligibleCount = 0;
                        $reason = ($meaningsWithExamplesCount === 0)
                            ? trans('study.reasons.no_examples')
                            : trans('study.reasons.no_suitable_cloze');
                    }
                } else {
                    $eligibleCount = $clozeEligibleCount;
                    $type['can_use_ai_cloze'] = $isAiConfigured;
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
