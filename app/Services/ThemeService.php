<?php

namespace App\Services;

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ThemeService
{
    /**
     * Tên cookie lưu trữ theme cho khách vãng lai hoặc client sync.
     */
    public const COOKIE_NAME = 'polyvocab_theme';

    /**
     * Xác định theme hiện tại của request.
     * Thứ tự ưu tiên:
     * 1. Cài đặt trong tài khoản người dùng đăng nhập (users.theme)
     * 2. Giá trị cookie nếu hợp lệ
     * 3. Mặc định: light
     */
    public function resolveTheme(?User $user = null): Theme
    {
        $user = $user ?? Auth::user();

        if ($user && $user->theme instanceof Theme) {
            return $user->theme;
        }

        if ($user && is_string($user->theme)) {
            $parsed = Theme::tryFrom($user->theme);
            if ($parsed) {
                return $parsed;
            }
        }

        $cookieTheme = request()?->cookie(self::COOKIE_NAME);
        if ($cookieTheme && is_string($cookieTheme)) {
            $parsed = Theme::tryFrom($cookieTheme);
            if ($parsed) {
                return $parsed;
            }
        }

        return Theme::LIGHT;
    }

    /**
     * Lấy danh sách thông tin 5 chủ đề để hiển thị trên UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableThemes(): array
    {
        return Theme::options();
    }
}

