<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryExample extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sentence',
        'translation',
        'sort_order',
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
        ];
    }

    /**
     * Nghĩa tương ứng của câu ví dụ này.
     */
    public function entryMeaning(): BelongsTo
    {
        return $this->belongsTo(EntryMeaning::class, 'entry_meaning_id');
    }

    /**
     * Alias nghĩa tương ứng của câu ví dụ này.
     */
    public function meaning(): BelongsTo
    {
        return $this->entryMeaning();
    }
}
