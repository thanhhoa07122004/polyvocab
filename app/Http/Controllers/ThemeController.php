<?php

namespace App\Services;

namespace App\Http\Controllers;

use App\Enums\Theme;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ThemeController extends Controller
{
    /**
     * Cập nhật chủ đề giao diện cho người dùng hoặc phiên hiện tại.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', Rule::in(Theme::values())],
        ], [
            'theme.required' => 'Vui lòng chọn một chủ đề giao diện.',
            'theme.in' => 'Chủ đề giao diện được chọn không hợp lệ.',
        ]);

        $themeEnum = Theme::from($validated['theme']);
        $user = Auth::user();

        if ($user) {
            $user->update([
                'theme' => $themeEnum,
            ]);
        }

        // Tạo cookie 1 năm để đồng bộ ngay cả khi đăng xuất
        $cookie = cookie(
            ThemeService::COOKIE_NAME,
            $themeEnum->value,
            60 * 24 * 365,
            '/',
            null,
            null,
            false,
            false,
            'Lax'
        );

        return response()->json([
            'success' => true,
            'theme' => $themeEnum->value,
            'label' => $themeEnum->label(),
            'color_scheme' => $themeEnum->colorScheme(),
            'message' => 'Đã lưu chủ đề giao diện thành công.',
        ])->withCookie($cookie);
    }
}

