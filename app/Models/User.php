<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Kiểm tra xem người dùng có phải là quản trị viên không.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Danh sách các bộ từ do người dùng này tạo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Deck>
     */
    public function decks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Deck::class);
    }

    /**
     * Tiến độ học tập lặp lại ngắt quãng (SRS) của người dùng.
     */
    public function studyRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudyRecord::class);
    }

    /**
     * Lịch sử các phiên học của người dùng.
     */
    public function studySessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudySession::class);
    }
}
