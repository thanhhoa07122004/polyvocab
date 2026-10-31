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
        'audio_path',
        'audio_source',
        'audio_voice',
        'audio_hash',
        'audio_char_count',
    ];

    /**
     * Kiểm tra xem mục từ đã có file âm thanh hợp lệ chưa.
     */
    public function hasAudio(): bool
    {
        return ! empty($this->audio_path);
    }

    /**
     * Kiểm tra xem âm thanh này có phải là audio tải lên thủ công không.
     */
    public function isManualAudio(): bool
    {
        return $this->audio_source === 'manual';
    }

    /**
     * Kiểm tra xem âm thanh này có phải do Azure TTS tạo không.
     */
    public function isTtsAudio(): bool
    {
        return $this->audio_source === 'azure_tts';
    }

    /**
     * Đường dẫn URL phát âm thanh qua endpoint bảo mật.
     */
    public function getAudioUrlAttribute(): ?string
    {
        if (! $this->hasAudio()) {
            return null;
        }

        return route('audio.entries.play', ['entry' => $this->id]);
    }

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

