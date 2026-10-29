<?php

namespace App\Policies;

use App\Models\Deck;
use App\Models\User;
use App\Models\VocabularyEntry;

class VocabularyEntryPolicy
{
    /**
     * Xác định người dùng có thể xem mục từ vựng không.
     * Khách và người dùng khác chỉ xem được mục từ thuộc bộ từ công khai.
     * Mục từ thuộc bộ từ riêng tư chỉ chủ sở hữu được xem.
     * Quản trị viên không tự động có quyền xem mục từ trong bộ riêng tư của người khác.
     */
    public function view(?User $user, VocabularyEntry $entry): bool
    {
        $deck = $entry->deck;

        if ($deck->isPublic()) {
            return true;
        }

        return $user !== null && $deck->user_id === $user->id;
    }

    /**
     * Xác định người dùng có thể tạo mục từ vựng trong bộ từ này không.
     * Chỉ chủ sở hữu bộ từ mới được tạo.
     */
    public function create(User $user, Deck $deck): bool
    {
        return $deck->user_id === $user->id;
    }

    /**
     * Xác định người dùng có thể chỉnh sửa mục từ vựng không.
     * Chỉ chủ sở hữu bộ từ mới có quyền chỉnh sửa mục từ.
     */
    public function update(User $user, VocabularyEntry $entry): bool
    {
        return $entry->deck->user_id === $user->id;
    }

    /**
     * Xác định người dùng có thể xóa mục từ vựng không.
     * Chỉ chủ sở hữu bộ từ mới có quyền xóa mục từ.
     */
    public function delete(User $user, VocabularyEntry $entry): bool
    {
        return $entry->deck->user_id === $user->id;
    }
}

