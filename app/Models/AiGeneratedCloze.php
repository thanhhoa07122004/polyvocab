<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneratedCloze extends Model
{
    protected $fillable = [
        'entry_meaning_id',
        'learning_language_id',
        'term',
        'meaning',
        'sentence_full',
        'sentence_with_blank',
        'blank_answer',
        'acceptable_variants',
        'translation',
        'translation_with_blank',
        'explanation',
        'distractors',
    ];

    protected function casts(): array
    {
        return [
            'acceptable_variants' => 'array',
            'distractors' => 'array',
        ];
    }

    public function entryMeaning(): BelongsTo
    {
        return $this->belongsTo(EntryMeaning::class);
    }

    public function learningLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'learning_language_id');
    }
}

