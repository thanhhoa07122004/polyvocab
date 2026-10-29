<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntryMeaning extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'part_of_speech',
        'meaning',
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
     * Mục từ chứa nghĩa này.
     */
    public function vocabularyEntry(): BelongsTo
    {
        return $this->belongsTo(VocabularyEntry::class, 'vocabulary_entry_id');
    }

    /**
     * Alias quan hệ mục từ chứa nghĩa này.
     */
    public function entry(): BelongsTo
    {
        return $this->vocabularyEntry();
    }

    /**
     * Danh sách các câu ví dụ cho nghĩa này.
     */
    public function examples(): HasMany
    {
        return $this->hasMany(EntryExample::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Bản ghi tiến độ học tập của nghĩa này.
     */
    public function studyRecords(): HasMany
    {
        return $this->hasMany(StudyRecord::class);
    }

    /**
     * Các mục trong phiên học có chứa nghĩa này.
     */
    public function studySessionItems(): HasMany
    {
        return $this->hasMany(StudySessionItem::class);
    }
}
