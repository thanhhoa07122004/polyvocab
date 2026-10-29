<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Hiển thị trang tổng quan quản trị hệ thống.
     */
    public function __invoke(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_members' => User::where('role', UserRole::MEMBER)->count(),
            'total_admins' => User::where('role', UserRole::ADMIN)->count(),
        ];

        $recentUsers = User::latest()
            ->take(10)
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }
}
