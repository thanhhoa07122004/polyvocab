<?php

namespace App\Enums;

enum StudySessionStatus: string
{
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Abandoned = 'abandoned';

    /**
     * Tên nhãn hiển thị tiếng Việt.
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Đang học',
            self::Paused => 'Tạm dừng',
            self::Completed => 'Đã hoàn thành',
            self::Abandoned => 'Đã hủy',
        };
    }
}

