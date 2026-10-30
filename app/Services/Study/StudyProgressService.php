<?php

namespace App\Services\Study;

use App\Enums\StudySessionStatus;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\StudyRecord;
use App\Models\StudySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class StudyProgressService
{
    /**
     * Query các bản ghi SRS đến hạn hoặc quá hạn của người dùng.
     * Quy tắc:
     * - Chỉ lấy nghĩa mà người dùng còn quyền truy cập bộ từ.
     * - Không chọn trùng nghĩa đang nằm trong các phiên học/ôn đang hoạt động (in_progress).
     * - Ưu tiên hạn ôn cũ nhất (due_at ASC).
     */
    public function getDueRecordsQuery(
        User $user,
        ?array $deckIds = null,
        ?int $languageId = null,
        bool $excludeActiveSessions = true
    ): Builder {
        $query = StudyRecord::query()
            ->with(['entryMeaning.vocabularyEntry.deck.learningLanguage', 'entryMeaning.examples'])
            ->where('user_id', $user->id)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->whereHas('deck', function (Builder $dq) use ($user) {
                // Người dùng còn quyền truy cập bộ từ: sở hữu hoặc công khai
                $dq->where('user_id', $user->id)
                    ->orWhere('visibility', 'public');
            })
            ->orderBy('due_at', 'asc');

        if (! empty($deckIds)) {
            $query->whereIn('deck_id', $deckIds);
        }

        if ($languageId) {
            $query->whereHas('deck', function (Builder $dq) use ($languageId) {
                $dq->where('learning_language_id', $languageId)
                    ->orWhere('meaning_language_id', $languageId);
            });
        }

        if ($excludeActiveSessions) {
            $activeMeaningIds = \App\Models\StudySessionItem::query()
                ->whereHas('session', function (Builder $sq) use ($user) {
                    $sq->where('user_id', $user->id)
                        ->where('status', StudySessionStatus::InProgress);
                })
                ->pluck('entry_meaning_id');

            if ($activeMeaningIds->isNotEmpty()) {
                $query->whereNotIn('entry_meaning_id', $activeMeaningIds);
            }
        }

        return $query;
    }

    /**
     * Lấy tổng số nghĩa đã đến hạn ôn tập của người dùng.
     */
    public function getDueMeaningsCount(User $user, ?Deck $deck = null): int
    {
        $deckIds = $deck ? [$deck->id] : null;

        return $this->getDueRecordsQuery($user, $deckIds, excludeActiveSessions: false)->count();
    }

    /**
     * Lấy số nghĩa quá hạn (overdue).
     * Lưu ý: "Quá hạn" là một phần con của "đến hạn" (đã quá hạn hơn 24 giờ), không cộng hai số thành tổng.
     */
    public function getOverdueMeaningsCount(User $user, ?Deck $deck = null): int
    {
        $deckIds = $deck ? [$deck->id] : null;

        return $this->getDueRecordsQuery($user, $deckIds, excludeActiveSessions: false)
            ->where('due_at', '<=', now()->subHours(24))
            ->count();
    }

    /**
     * Lấy thời điểm đến hạn ôn tập gần nhất tiếp theo (khi hiện tại chưa có từ nào đến hạn).
     */
    public function getNextDueReviewTime(User $user, ?Deck $deck = null): ?Carbon
    {
        $nextDue = StudyRecord::query()
            ->where('user_id', $user->id)
            ->when($deck, fn (Builder $q) => $q->where('deck_id', $deck->id))
            ->whereNotNull('due_at')
            ->where('due_at', '>', now())
            ->min('due_at');

        return $nextDue ? Carbon::parse($nextDue) : null;
    }

    /**
     * Kiểm tra người dùng đã có lịch học/ôn tập nào chưa.
     */
    public function hasAnyStudiedMeanings(User $user, ?Deck $deck = null): bool
    {
        return StudyRecord::query()
            ->where('user_id', $user->id)
            ->when($deck, fn (Builder $q) => $q->where('deck_id', $deck->id))
            ->exists();
    }

    /**
     * Query các nghĩa chưa học trong bộ từ đối với người dùng này.
     * Đơn vị học tập cốt lõi là một nghĩa (EntryMeaning).
     */
    public function getUnstudiedMeaningsQuery(User $user, Deck $deck): Builder
    {
        return EntryMeaning::query()
            ->whereHas('vocabularyEntry', function (Builder $q) use ($deck) {
                $q->where('deck_id', $deck->id);
            })
            ->whereDoesntHave('studyRecords', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where(function (Builder $sq) {
                        $sq->whereNotNull('familiarized_at')
                            ->orWhereNotNull('last_studied_at')
                            ->orWhere('repetition_level', '>', 0);
                    });
            });
    }

    /**
     * Đếm số nghĩa mới khả dụng chưa học trong bộ từ.
     */
    public function getUnstudiedMeaningsCount(User $user, Deck $deck): int
    {
        return $this->getUnstudiedMeaningsQuery($user, $deck)->count();
    }

    /**
     * Query các nghĩa đã từng học trong bộ từ (dành cho chế độ Luyện thêm hoặc Kiểm tra).
     */
    public function getStudiedMeaningsQuery(User $user, ?array $deckIds = null): Builder
    {
        return EntryMeaning::query()
            ->whereHas('studyRecords', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where(function (Builder $sq) {
                        $sq->whereNotNull('familiarized_at')
                            ->orWhereNotNull('last_studied_at')
                            ->orWhere('repetition_level', '>', 0);
                    });
            })
            ->when($deckIds, function (Builder $q) use ($deckIds) {
                $q->whereHas('vocabularyEntry', fn ($vq) => $vq->whereIn('deck_id', $deckIds));
            });
    }

    /**
     * Lấy phiên học đang diễn ra (chưa kết thúc) của người dùng.
     */
    public function getActiveSession(User $user, ?Deck $deck = null, ?\App\Enums\StudyMode $mode = null): ?StudySession
    {
        return StudySession::query()
            ->with(['deck.learningLanguage', 'deck.meaningLanguage', 'items.entryMeaning.vocabularyEntry'])
            ->where('user_id', $user->id)
            ->when($deck, fn (Builder $q) => $q->where('deck_id', $deck->id))
            ->when($mode, fn (Builder $q) => $q->where('mode', $mode))
            ->where('status', StudySessionStatus::InProgress)
            ->latest('last_interacted_at')
            ->first();
    }

    /**
     * Query các nghĩa được đánh dấu khó hoặc có kết quả yếu (lapse_count >= 2) của người dùng.
     */
    public function getDifficultMeaningsQuery(User $user, ?Deck $deck = null): Builder
    {
        return EntryMeaning::query()
            ->whereHas('studyRecords', function (Builder $q) use ($user, $deck) {
                $q->where('user_id', $user->id)
                    ->where(function (Builder $sq) {
                        $sq->where('is_difficult', true)
                            ->orWhere('lapse_count', '>=', 2);
                    })
                    ->when($deck, fn (Builder $dq) => $dq->where('deck_id', $deck->id));
            })
            ->when($deck, function (Builder $q) use ($deck) {
                $q->whereHas('vocabularyEntry', fn (Builder $vq) => $vq->where('deck_id', $deck->id));
            });
    }

    /**
     * Đếm số nghĩa được đánh dấu khó hoặc có kết quả yếu.
     */
    public function getDifficultMeaningsCount(User $user, ?Deck $deck = null): int
    {
        return $this->getDifficultMeaningsQuery($user, $deck)->count();
    }

    /**
     * Lấy toàn bộ số liệu thống kê học tập cho một bộ từ cụ thể (dành cho trang decks/show).
     */
    public function getDeckStudyStats(User $user, Deck $deck): array
    {
        $totalEntries = $deck->entries()->count();
        $totalMeanings = EntryMeaning::whereHas('vocabularyEntry', fn ($q) => $q->where('deck_id', $deck->id))->count();
        $unstudiedCount = $this->getUnstudiedMeaningsCount($user, $deck);
        $dueCount = $this->getDueMeaningsCount($user, $deck);
        $overdueCount = $this->getOverdueMeaningsCount($user, $deck);
        $difficultCount = $this->getDifficultMeaningsCount($user, $deck);
        $activeSession = $this->getActiveSession($user, $deck);

        return [
            'total_entries' => $totalEntries,
            'total_meanings' => $totalMeanings,
            'unstudied_count' => $unstudiedCount,
            'due_count' => $dueCount,
            'overdue_count' => $overdueCount,
            'difficult_count' => $difficultCount,
            'active_session' => $activeSession,
        ];
    }

    /**
     * Query các nghĩa phục vụ chế độ Luyện tập theo phạm vi tự chọn.
     */
    public function getEligibleMeaningsForPractice(
        User $user,
        array $deckIds,
        string $scope = 'all',
        bool $onlyDifficult = false
    ): Builder {
        $query = EntryMeaning::query()
            ->whereHas('vocabularyEntry', fn (Builder $q) => $q->whereIn('deck_id', $deckIds));

        if ($onlyDifficult || $scope === 'difficult') {
            $query->whereHas('studyRecords', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where(function (Builder $sq) {
                        $sq->where('is_difficult', true)
                            ->orWhere('lapse_count', '>=', 2);
                    });
            });
        } elseif ($scope === 'unstudied') {
            $query->whereDoesntHave('studyRecords', fn (Builder $q) => $q->where('user_id', $user->id)->whereNotNull('familiarized_at'));
        } elseif ($scope === 'studied') {
            $query->whereHas('studyRecords', fn (Builder $q) => $q->where('user_id', $user->id)->whereNotNull('familiarized_at'));
        } elseif ($scope === 'due') {
            $query->whereHas('studyRecords', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)->where('due_at', '<=', now());
            });
        }

        return $query;
    }

    /**
     * Tổng hợp thông tin học tập thực tế cho Trang tổng quan (Dashboard).
     * Tuyệt đối không hiển thị số liệu giả để lấp giao diện.
     */
    public function getUserDashboardStats(User $user): array
    {
        $userDecksCount = $user->decks()->count();
        $decksWithWordsCount = $user->decks()->whereHas('entries')->count();

        $dueCount = $this->getDueMeaningsCount($user);
        $overdueCount = $this->getOverdueMeaningsCount($user);
        $nextDueAt = $dueCount === 0 ? $this->getNextDueReviewTime($user) : null;
        $hasStudied = $this->hasAnyStudiedMeanings($user);
        $activeSession = $this->getActiveSession($user);

        // Lấy danh sách các bộ từ gần đây của người dùng
        $recentDecks = $user->decks()
            ->with(['learningLanguage', 'meaningLanguage'])
            ->withCount('entries')
            ->latest('updated_at')
            ->take(6)
            ->get();

        return [
            'total_decks' => $userDecksCount,
            'decks_with_words_count' => $decksWithWordsCount,
            'due_count' => $dueCount,
            'overdue_count' => $overdueCount,
            'next_due_at' => $nextDueAt,
            'has_studied' => $hasStudied,
            'active_session' => $activeSession,
            'recent_decks' => $recentDecks,
        ];
    }
}
