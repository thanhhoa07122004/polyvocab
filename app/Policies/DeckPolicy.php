<?php

namespace App\Policies;

use App\Models\Deck;
use App\Models\User;

class DeckPolicy
{
    /**
     * Xác định người dùng có thể xem danh sách bộ từ của mình không.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Xác định người dùng (hoặc khách) có thể xem bộ từ hay không.
     * Khách và thành viên khác xem được bộ từ công khai.
     * Bộ từ riêng tư chỉ chủ sở hữu được xem.
     * Quản trị viên không tự động có quyền xem bộ từ riêng tư của người khác.
     */
    public function view(?User $user, Deck $deck): bool
    {
        if ($deck->isPublic()) {
            return true;
        }

        return $user !== null && $deck->user_id === $user->id;
    }

    /**
     * Xác định người dùng có thể tạo bộ từ không.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Xác định người dùng có thể chỉnh sửa bộ từ không.
     * Chỉ chủ sở hữu mới có quyền chỉnh sửa bộ từ.
     * Quản trị viên không tự động có quyền sửa bộ từ của người khác.
     */
    public function update(User $user, Deck $deck): bool
    {
        return $deck->user_id === $user->id;
    }

    /**
     * Xác định người dùng có thể xóa bộ từ không.
     * Chỉ chủ sở hữu mới có quyền xóa bộ từ.
     * Quản trị viên không tự động có quyền xóa bộ từ của người khác.
     */
    public function delete(User $user, Deck $deck): bool
    {
        return $deck->user_id === $user->id;
    }
}

