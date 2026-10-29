<?php

namespace App\Http\Controllers;

use App\Services\Study\StudyProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected StudyProgressService $progressService
    ) {}

    /**
     * Hiển thị trang bảng điều khiển của người dùng.
     * Cung cấp dữ liệu học tập thực tế và các hành động chính.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $stats = $this->progressService->getUserDashboardStats($user);

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
