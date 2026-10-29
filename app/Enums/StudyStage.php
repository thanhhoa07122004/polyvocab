<?php

namespace App\Enums;

enum StudyStage: string
{
    case Familiarization = 'familiarization';
    case RetentionPractice = 'retention_practice';

    /**
     * Tên nhãn hiển thị tiếng Việt.
     */
    public function label(): string
    {
        return match ($this) {
            self::Familiarization => 'Làm quen',
            self::RetentionPractice => 'Luyện nhớ',
        };
    }

    /**
     * Mô tả giai đoạn học.
     */
    public function description(): string
    {
        return match ($this) {
            self::Familiarization => 'Tìm hiểu từ vựng, phiên âm, ý nghĩa và câu ví dụ minh họa.',
            self::RetentionPractice => 'Luyện tập qua câu hỏi để kích hoạt và củng cố trí nhớ.',
        };
    }
}

