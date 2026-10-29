<?php

namespace App\Enums;

enum UserRole: string
{
    case MEMBER = 'member';
    case ADMIN = 'admin';

    /**
     * Nhãn hiển thị tiếng Việt của vai trò.
     */
    public function label(): string
    {
        return match ($this) {
            self::MEMBER => 'Thành viên',
            self::ADMIN => 'Quản trị viên',
        };
    }

    /**
     * Kiểm tra xem vai trò có phải là quản trị viên không.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
