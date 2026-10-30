<?php

namespace App\Models;

use App\Enums\StudyStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySessionItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'study_session_id',
        'entry_meaning_id',
        'sort_order',
        'chunk_index',
        'stage',
        'familiarized_at',
        'practiced_at',
        'is_difficult',
        'is_self_declared_known',
        'practice_result',
        'first_attempt_result',
        'attempts_count',
        'used_hint',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'chunk_index' => 'integer',
            'stage' => StudyStage::class,
            'familiarized_at' => 'datetime',
            'practiced_at' => 'datetime',
            'is_difficult' => 'boolean',
            'is_self_declared_known' => 'boolean',
            'attempts_count' => 'integer',
            'used_hint' => 'boolean',
        ];
    }

    /**
     * Phiên học chứa mục này.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'study_session_id');
    }

    /**
     * Nghĩa của từ vựng (đơn vị tiến độ).
     */
    public function entryMeaning(): BelongsTo
    {
        return $this->belongsTo(EntryMeaning::class);
    }
}

