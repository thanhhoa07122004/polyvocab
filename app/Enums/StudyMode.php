<?php

namespace App\Enums;

enum StudyMode: string
{
    case LearnNew = 'learn_new';
    case Review = 'review';
    case PracticeCustom = 'custom_practice';
    case PracticeDifficult = 'difficult_practice';
    case Quiz = 'quiz';

    /**
     * Tên nhãn hiển thị tiếng Việt.
     */
    public function label(): string
    {
        return match ($this) {
            self::LearnNew => 'Học từ mới',
            self::Review => 'Ôn đến hạn',
            self::PracticeCustom => 'Luyện theo dạng bài',
            self::PracticeDifficult => 'Luyện từ khó',
            self::Quiz => 'Kiểm tra',
        };
    }

    /**
     * Mô tả mục đích chế độ học.
     */
    public function description(): string
    {
        return match ($this) {
            self::LearnNew => 'Làm quen và luyện nhớ các nghĩa chưa học trong bộ từ, khởi tạo tiến độ và lịch ôn.',
            self::Review => 'Củng cố các nghĩa đến hạn hoặc quá hạn theo lịch lặp lại ngắt quãng (SRS).',
            self::PracticeCustom => 'Luyện tập theo phạm vi và dạng bài tự chọn. Mặc định không thay đổi lịch ôn SRS.',
            self::PracticeDifficult => 'Luyện tập củng cố các nghĩa được đánh dấu khó hoặc có kết quả yếu. Mặc định không thay đổi lịch ôn SRS.',
            self::Quiz => 'Đánh giá kiến thức và mức độ ghi nhớ trong phạm vi lựa chọn.',
        };
    }

    /**
     * Chế độ này có cập nhật lịch ôn SRS không.
     */
    public function isSrsModifying(): bool
    {
        return match ($this) {
            self::LearnNew, self::Review => true,
            self::PracticeCustom, self::PracticeDifficult, self::Quiz => false,
        };
    }
}

