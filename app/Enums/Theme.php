<?php

namespace App\Enums;

enum Theme: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case ROSE_PASTEL = 'rose_pastel';
    case PLUM_DARK = 'plum_dark';
    case BLUE_LIGHT = 'blue_light';

    /**
     * Tên hiển thị tiếng Việt của chủ đề.
     */
    public function label(): string
    {
        return match ($this) {
            self::LIGHT => 'Trắng',
            self::DARK => 'Tối',
            self::ROSE_PASTEL => 'Hồng pastel',
            self::PLUM_DARK => 'Tím pastel tối',
            self::BLUE_LIGHT => 'Xanh dương nhạt',
        };
    }

    /**
     * Thuộc tính color-scheme của trình duyệt.
     */
    public function colorScheme(): string
    {
        return match ($this) {
            self::LIGHT, self::ROSE_PASTEL, self::BLUE_LIGHT => 'light',
            self::DARK, self::PLUM_DARK => 'dark',
        };
    }

    /**
     * Mô tả đặc điểm giao diện.
     */
    public function description(): string
    {
        return match ($this) {
            self::LIGHT => 'Giao diện sáng tiêu chuẩn, nền trắng tinh gọn và độ tương phản cao.',
            self::DARK => 'Nền tối chống mỏi mắt ban đêm, dịu nhẹ và tiết kiệm pin.',
            self::ROSE_PASTEL => 'Tông hồng pastel ấm áp, nền dịu mắt và điểm nhấn mận chín.',
            self::PLUM_DARK => 'Nền tím trầm kết hợp điểm nhấn tím nhạt dịu mắt, không chói.',
            self::BLUE_LIGHT => 'Nền xanh băng thanh thoát, phong cách tập trung và hiện đại.',
        };
    }

    /**
     * Bảng màu thu nhỏ (swatch) để hiển thị trong bộ chọn chủ đề.
     *
     * @return array<string, string>
     */
    public function previewColors(): array
    {
        return match ($this) {
            self::LIGHT => [
                'bg' => '#FFFFFF',
                'card' => '#F8FAFC',
                'text' => '#0F172A',
                'primary' => '#4338CA',
            ],
            self::DARK => [
                'bg' => '#11161D',
                'card' => '#1B222C',
                'text' => '#E6EDF3',
                'primary' => '#818CF8',
            ],
            self::ROSE_PASTEL => [
                'bg' => '#FFF1F5',
                'card' => '#FFFFFF',
                'text' => '#4A2535',
                'primary' => '#A82D60',
            ],
            self::PLUM_DARK => [
                'bg' => '#181322',
                'card' => '#251E34',
                'text' => '#F3EEF9',
                'primary' => '#C4A7EC',
            ],
            self::BLUE_LIGHT => [
                'bg' => '#EFF6FF',
                'card' => '#FFFFFF',
                'text' => '#18324F',
                'primary' => '#1D4ED8',
            ],
        };
    }

    /**
     * Chủ đề mặc định khi chưa có lựa chọn.
     */
    public static function defaultTheme(): self
    {
        return self::LIGHT;
    }

    /**
     * Kiểm tra xem giá trị có phải chủ đề hợp lệ không.
     */
    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * Danh sách tất cả các giá trị chuỗi của chủ đề.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Cung cấp danh sách thông tin đầy đủ để dựng giao diện bộ chọn (Theme Switcher).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function options(): array
    {
        return array_map(function (self $theme) {
            return [
                'value' => $theme->value,
                'label' => $theme->label(),
                'color_scheme' => $theme->colorScheme(),
                'description' => $theme->description(),
                'preview_colors' => $theme->previewColors(),
            ];
        }, self::cases());
    }
}
