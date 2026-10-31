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

class CreateLearnNewSessionAction
{
    public function __construct(
        protected StudyProgressService $progressService
    ) {}

    /**
     * Tạo hoặc lấy lại phiên học từ mới đang dang dở.
     * Tránh tạo trùng lặp phiên khi người dùng tải lại trang.
     */
    public function execute(
        User $user,
        Deck $deck,
        int $requestedCount,
        array $selectedExerciseTypes = []
    ): ?StudySession {
        // Kiểm tra xem đã có phiên đang học dở của bộ từ này không
        $existingSession = $this->progressService->getActiveSession($user, $deck);
        if ($existingSession && $existingSession->mode === StudyMode::LearnNew) {
            return $existingSession;
        }

        // Giới hạn số lượng mục từ học theo cấu hình
        $minBatch = (int) config('polyvocab.study.min_batch_size', 1);
        $maxBatch = (int) config('polyvocab.study.max_batch_size', 50);
        $count = max($minBatch, min($maxBatch, $requestedCount));

        // Lấy danh sách nghĩa để học (ưu tiên từ chưa học, nếu đã học hết thì lấy từ trong bộ để học lại)
        $unstudiedMeanings = $this->progressService->getUnstudiedMeaningsQuery($user, $deck)
            ->with(['vocabularyEntry', 'examples'])
            ->take($count)
            ->get();

        if ($unstudiedMeanings->isEmpty()) {
            $unstudiedMeanings = $deck->meanings()
                ->with(['vocabularyEntry', 'examples'])
                ->take($count)
                ->get();
        }

        if ($unstudiedMeanings->isEmpty()) {
            return null;
        }

        $chunkSize = $unstudiedMeanings->count();

        return DB::transaction(function () use ($user, $deck, $unstudiedMeanings, $chunkSize) {
            $session = StudySession::create([
                'user_id' => $user->id,
                'deck_id' => $deck->id,
                'mode' => StudyMode::LearnNew,
                'stage' => StudyStage::Familiarization,
                'status' => StudySessionStatus::InProgress,
                'chunk_size' => $chunkSize,
                'current_chunk' => 0,
                'settings' => [
                    'total_target' => $unstudiedMeanings->count(),
                ],
                'last_interacted_at' => now(),
            ]);

            foreach ($unstudiedMeanings as $index => $meaning) {
                StudySessionItem::create([
                    'study_session_id' => $session->id,
                    'entry_meaning_id' => $meaning->id,
                    'sort_order' => $index,
                    'chunk_index' => 0,
                    'stage' => StudyStage::Familiarization,
                ]);
            }

            return $session->load(['items.entryMeaning.vocabularyEntry', 'items.entryMeaning.examples', 'deck']);
        });
    }
}

