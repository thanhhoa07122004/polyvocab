<?php

namespace App\Models;

use App\Enums\DeckVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Deck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Lưu ý: Không đưa user_id vào fillable để tránh giả mạo từ client.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'learning_language_id',
        'meaning_language_id',
        'visibility',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => DeckVisibility::class,
        ];
    }

    /**
     * Scope lọc các bộ từ công khai.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', DeckVisibility::PUBLIC->value);
    }

    /**
     * Scope lọc các bộ từ thuộc về một người dùng cụ thể.
     */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    /**
     * Kiểm tra xem bộ từ có đang ở chế độ công khai không.
     */
    public function isPublic(): bool
    {
        return $this->visibility === DeckVisibility::PUBLIC;
    }

    /**
     * Kiểm tra xem bộ từ có đang ở chế độ riêng tư không.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === DeckVisibility::PRIVATE;
    }

    /**
     * Người sở hữu bộ từ.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ngôn ngữ đang học của bộ từ.
     */
    public function learningLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'learning_language_id');
    }

    /**
     * Ngôn ngữ giải nghĩa của bộ từ.
     */
    public function meaningLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'meaning_language_id');
    }

    /**
     * Danh sách các mục từ vựng trong bộ từ.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\VocabularyEntry>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(VocabularyEntry::class)->latest();
    }

    /**
     * Danh sách tất cả các nét nghĩa trong các mục từ của bộ từ.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\App\Models\EntryMeaning>
     */
    public function meanings(): HasManyThrough
    {
        return $this->hasManyThrough(EntryMeaning::class, VocabularyEntry::class);
    }

    /**
     * Bản ghi tiến độ học tập trong bộ từ này.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\StudyRecord>
     */
    public function studyRecords(): HasMany
    {
        return $this->hasMany(StudyRecord::class);
    }

    /**
     * Các phiên học liên quan đến bộ từ này.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\StudySession>
     */
    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }
}

