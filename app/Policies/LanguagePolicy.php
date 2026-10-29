<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;

class LanguagePolicy
{
    /**
     * Xác định người dùng có thể xem danh sách ngôn ngữ trong trang quản trị không.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Xác định người dùng có thể xem chi tiết ngôn ngữ không.
     */
    public function view(User $user, Language $language): bool
    {
        return $user->isAdmin();
    }

    /**
     * Xác định người dùng có thể tạo ngôn ngữ mới không.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Xác định người dùng có thể chỉnh sửa ngôn ngữ không.
     */
    public function update(User $user, Language $language): bool
    {
        return $user->isAdmin();
    }

    /**
     * Xác định người dùng có thể bật/tắt ngôn ngữ không.
     */
    public function toggle(User $user, Language $language): bool
    {
        return $user->isAdmin();
    }

    /**
     * Không hỗ trợ xóa ngôn ngữ để bảo vệ toàn vẹn dữ liệu.
     */
    public function delete(User $user, Language $language): bool
    {
        return false;
    }
}

