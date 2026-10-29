<?php

namespace App\Enums;

enum DeckVisibility: string
{
    case PRIVATE = 'private';
    case PUBLIC = 'public';

    /**
     * Nhãn hiển thị tiếng Việt của trạng thái hiển thị bộ từ.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRIVATE => 'Riêng tư',
            self::PUBLIC => 'Công khai',
        };
    }

    /**
     * Kiểm tra trạng thái có phải là công khai không.
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLIC;
    }

    /**
     * Kiểm tra trạng thái có phải là riêng tư không.
     */
    public function isPrivate(): bool
    {
        return $this === self::PRIVATE;
    }
}

