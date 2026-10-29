<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VocabularyEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Lưu ý: Không đưa deck_id vào fillable trực tiếp để bảo vệ đích ghi dữ liệu.
     *
     * @var list<string>
     */
    protected $fillable = [
        'term',
        'pronunciation',
        'note',
    ];

    /**
     * Bộ từ chứa mục từ vựng này.
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * Danh sách các nghĩa của mục từ vựng này.
     */
    public function meanings(): HasMany
    {
        return $this->hasMany(EntryMeaning::class)->orderBy('sort_order')->orderBy('id');
    }
}

