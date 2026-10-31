<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Language extends Model
{
    use HasFactory;

    /**
     * Cache key cho danh sách ngôn ngữ đang hoạt động.
     */
    public const ACTIVE_CACHE_KEY = 'languages:active';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'tts_locale',
        'tts_voice',
        'is_active',
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
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Khởi tạo các hook của model để tự động xóa cache khi có thay đổi.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearActiveCache();
        });

        static::deleted(function () {
            static::clearActiveCache();
        });
    }

    /**
     * Scope lọc danh sách các ngôn ngữ đang hoạt động.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Lấy danh sách ngôn ngữ hoạt động từ cache.
     *
     * @return Collection<int, Language>
     */
    public static function getActiveCached(): Collection
    {
        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, function () {
            return static::active()->get();
        });
    }

    /**
     * Làm mới cache danh sách ngôn ngữ hoạt động.
     */
    public static function clearActiveCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }

    /**
     * Kiểm tra ngôn ngữ có phải chiều từ phải sang trái (RTL) hay không.
     */
    public function isRtl(): bool
    {
        return strtolower($this->direction) === 'rtl';
    }

    /**
     * Danh sách các bộ từ sử dụng ngôn ngữ này để học.
     */
    public function learningDecks(): HasMany
    {
        return $this->hasMany(Deck::class, 'learning_language_id');
    }

    /**
     * Danh sách các bộ từ sử dụng ngôn ngữ này để giải nghĩa.
     */
    public function meaningDecks(): HasMany
    {
        return $this->hasMany(Deck::class, 'meaning_language_id');
    }
}

