<?php

namespace App\Actions\Study;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\StudySession;
use App\Models\StudySessionItem;
use App\Models\User;
use App\Services\Study\StudyProgressService;
use Illuminate\Support\Facades\DB;

class CreatePracticeSessionAction
{
    public function __construct(
        protected StudyProgressService $progressService
    ) {}

    /**
     * Tạo phiên luyện tập tự do theo dạng bài hoặc luyện từ khó.
     * Mặc định KHÔNG thay đổi lịch ôn Spaced Repetition (SRS) và KHÔNG tính vào mục tiêu học mới.
     *
     * @param User $user
     * @param array<int> $deckIds
     * @param string $scope 'all'|'studied'|'unstudied'|'difficult'
     * @param int $requestedCount
     * @param array<string> $selectedExerciseTypes
     * @param bool $isDifficultOnly
     * @return StudySession|null
     */
    public function execute(
        User $user,
        array $deckIds,
        string $scope = 'all',
        int $requestedCount = 10,
        array $selectedExerciseTypes = [],
        bool $isDifficultOnly = false,
        ?array $specificMeaningIds = null
    ): ?StudySession {
        // Kiểm tra quyền truy cập các bộ từ
        $accessibleDecks = Deck::query()
            ->whereIn('id', $deckIds)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('visibility', 'public');
            })
            ->get();

        if ($accessibleDecks->isEmpty()) {
            return null;
        }

        $validDeckIds = $accessibleDecks->pluck('id')->all();
        $primaryDeck = $accessibleDecks->first();

        // Giới hạn số lượng
        $minBatch = 1;
        $maxBatch = (int) config('polyvocab.study.practice.max_count', 50);
        $count = max($minBatch, min($maxBatch, $requestedCount));

        // Lấy danh sách nghĩa theo danh sách cụ thể hoặc theo scope
        if (! empty($specificMeaningIds)) {
            $meanings = EntryMeaning::query()
                ->whereIn('id', $specificMeaningIds)
                ->whereHas('vocabularyEntry', function ($q) use ($validDeckIds) {
                    $q->whereIn('deck_id', $validDeckIds);
                })
                ->with(['vocabularyEntry', 'examples'])
                ->get();
        } else {
            $meanings = $this->progressService
                ->getEligibleMeaningsForPractice($user, $validDeckIds, $scope, $isDifficultOnly)
                ->with(['vocabularyEntry', 'examples'])
                ->inRandomOrder()
                ->take($count)
                ->get();
        }

        if ($meanings->isEmpty()) {
            return null;
        }

        $mode = ($isDifficultOnly || $scope === 'difficult')
            ? StudyMode::PracticeDifficult
            : StudyMode::PracticeCustom;

        return DB::transaction(function () use ($user, $primaryDeck, $meanings, $mode, $scope, $selectedExerciseTypes, $validDeckIds) {
            $chunkSize = 5;

            $session = StudySession::create([
                'user_id' => $user->id,
                'deck_id' => $primaryDeck->id,
                'mode' => $mode,
                'stage' => StudyStage::RetentionPractice,
                'status' => StudySessionStatus::InProgress,
                'chunk_size' => $chunkSize,
                'current_chunk' => 0,
                'settings' => [
                    'exercise_types' => $selectedExerciseTypes,
                    'scope' => $scope,
                    'is_extra_practice' => true, // Bảo vệ không thay đổi lịch ôn SRS
                    'deck_ids' => $validDeckIds,
                    'total_target' => $meanings->count(),
                    'notice' => 'Phiên luyện này không thay đổi lịch ôn và không tính vào mục tiêu học mới.',
                ],
                'last_interacted_at' => now(),
            ]);

            foreach ($meanings as $index => $meaning) {
                StudySessionItem::create([
                    'study_session_id' => $session->id,
                    'entry_meaning_id' => $meaning->id,
                    'sort_order' => $index,
                    'chunk_index' => (int) floor($index / $chunkSize),
                    'stage' => StudyStage::RetentionPractice,
                    'familiarized_at' => now(), // Đánh dấu đã quen để vào thẳng luyện nhớ
                ]);
            }

            return $session->load(['items.entryMeaning.vocabularyEntry', 'deck']);
        });
    }
}

