<?php

namespace App\Actions\Study;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Models\Deck;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Models\User;
use App\Services\Study\StudyProgressService;
use Illuminate\Support\Facades\DB;

class CreateReviewSessionAction
{
    public function __construct(
        protected StudyProgressService $progressService
    ) {}

    /**
     * Khởi tạo một phiên ôn tập (hoặc phiên luyện thêm ngoài lịch).
     */
    public function execute(
        User $user,
        ?array $deckIds = null,
        ?int $languageId = null,
        int $requestedCount = 10,
        bool $isExtraPractice = false,
        array $selectedExerciseTypes = [],
        bool $useAiCloze = false
    ): ?StudySession {
        $chunkSize = (int) config('polyvocab.study.chunk_size', 5);
        $minBatch = (int) config('polyvocab.study.min_batch_size', 1);
        $maxBatch = (int) config('polyvocab.study.max_batch_size', 50);
        $count = max($minBatch, min($maxBatch, $requestedCount));

        if ($isExtraPractice) {
            // Chế độ luyện thêm ngoài lịch: Lấy từ vựng đã từng học
            $meanings = $this->progressService->getStudiedMeaningsQuery($user, $deckIds)
                ->with(['vocabularyEntry.deck.learningLanguage', 'examples'])
                ->inRandomOrder()
                ->take($count)
                ->get();
        } else {
            // Chế độ ôn tập theo lịch đến hạn (ưu tiên hạn cũ nhất)
            $records = $this->progressService->getDueRecordsQuery($user, $deckIds, $languageId, excludeActiveSessions: true)
                ->take($count)
                ->get();

            $meanings = $records->map->entryMeaning->filter();
        }

        if ($meanings->isEmpty()) {
            return null;
        }

        // Chọn deck_id đại diện (deck của mục đầu tiên)
        $primaryDeckId = $meanings->first()->vocabularyEntry->deck_id;

        return DB::transaction(function () use ($user, $primaryDeckId, $meanings, $chunkSize, $isExtraPractice, $selectedExerciseTypes, $deckIds, $useAiCloze) {
            $session = StudySession::create([
                'user_id' => $user->id,
                'deck_id' => $primaryDeckId,
                'mode' => StudyMode::Review,
                'stage' => StudyStage::RetentionPractice,
                'status' => StudySessionStatus::InProgress,
                'chunk_size' => $chunkSize,
                'current_chunk' => 0,
                'settings' => [
                    'is_extra_practice' => $isExtraPractice,
                    'exercise_types' => $selectedExerciseTypes,
                    'use_ai_cloze' => $useAiCloze,
                    'selected_deck_ids' => $deckIds,
                    'total_target' => $meanings->count(),
                    'finalized_meanings' => [],
                    'retries_count' => [],
                ],
                'last_interacted_at' => now(),
            ]);

            foreach ($meanings->values() as $index => $meaning) {
                StudySessionItem::create([
                    'study_session_id' => $session->id,
                    'entry_meaning_id' => $meaning->id,
                    'sort_order' => $index,
                    'chunk_index' => (int) floor($index / $chunkSize),
                    'stage' => StudyStage::RetentionPractice,
                ]);
            }

            return $session->load(['items.entryMeaning.vocabularyEntry.deck', 'items.entryMeaning.examples']);
        });
    }
}

