<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TtsUsage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'vocabulary_entry_id',
        'characters_used',
        'locale',
        'voice',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'characters_used' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vocabularyEntry(): BelongsTo
    {
        return $this->belongsTo(VocabularyEntry::class);
    }
}

