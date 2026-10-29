<?php

namespace App\Models;

use App\Enums\StudyMode;
use App\Enums\StudySessionStatus;
use App\Enums\StudyStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'deck_id',
        'mode',
        'stage',
        'status',
        'chunk_size',
        'current_chunk',
        'settings',
        'last_interacted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => StudyMode::class,
            'stage' => StudyStage::class,
            'status' => StudySessionStatus::class,
            'chunk_size' => 'integer',
            'current_chunk' => 'integer',
            'settings' => 'array',
            'last_interacted_at' => 'datetime',
        ];
    }

    /**
     * Người học tham gia phiên học này.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bộ từ vựng được học trong phiên này.
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * Danh sách các mục nghĩa trong phiên học.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StudySessionItem::class)->orderBy('chunk_index')->orderBy('sort_order');
    }

    /**
     * Kiểm tra phiên có đang hoạt động hay không.
     */
    public function isInProgress(): bool
    {
        return $this->status === StudySessionStatus::InProgress;
    }

    /**
     * Lấy các mục thuộc nhóm hiện tại.
     */
    public function currentChunkItems()
    {
        return $this->items()->where('chunk_index', $this->current_chunk);
    }

    /**
     * Tổng số nhóm trong phiên.
     */
    public function totalChunks(): int
    {
        $maxChunk = $this->items()->max('chunk_index');

        return $maxChunk !== null ? (int) $maxChunk + 1 : 1;
    }

    /**
     * Số mục đã hoàn thành trong toàn bộ phiên.
     */
    public function completedItemsCount(): int
    {
        return $this->items()
            ->where(function ($query) {
                $query->whereNotNull('practiced_at')
                    ->orWhereNotNull('familiarized_at');
            })
            ->count();
    }
}

