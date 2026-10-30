<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyRecord extends Model
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
        'entry_meaning_id',
        'familiarized_at',
        'repetition_level',
        'ease_factor',
        'interval_days',
        'due_at',
        'review_count',
        'lapse_count',
        'is_self_declared_known',
        'is_difficult',
        'last_studied_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'familiarized_at' => 'datetime',
            'repetition_level' => 'integer',
            'ease_factor' => 'decimal:2',
            'interval_days' => 'integer',
            'due_at' => 'datetime',
            'review_count' => 'integer',
            'lapse_count' => 'integer',
            'is_self_declared_known' => 'boolean',
            'is_difficult' => 'boolean',
            'last_studied_at' => 'datetime',
        ];
    }

    /**
     * Người học sở hữu bản ghi tiến độ này.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bộ từ vựng chứa nghĩa này.
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * Nghĩa của từ vựng đang được theo dõi tiến độ (đơn vị học tập cốt lõi).
     */
    public function entryMeaning(): BelongsTo
    {
        return $this->belongsTo(EntryMeaning::class);
    }

    /**
     * Kiểm tra xem nghĩa này đã đến hạn ôn tập hay chưa.
     */
    public function isDue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }
}

