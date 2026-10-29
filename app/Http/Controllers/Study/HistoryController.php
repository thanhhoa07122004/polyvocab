<?php

namespace App\Http\Controllers\Study;

use App\Enums\StudyMode;
use App\Http\Controllers\Controller;
use App\Models\StudySession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Danh sách lịch sử học tập, ôn tập và kiểm tra của cá nhân người dùng.
     * Chỉ chủ sở hữu mới có quyền xem lịch sử của mình (Admin không mặc định được xem).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $mode = $request->query('mode');
        $timeframe = $request->query('timeframe'); // 7days, 30days, all

        $query = StudySession::query()
            ->with(['deck.learningLanguage', 'deck.meaningLanguage'])
            ->withCount('items')
            ->where('user_id', $user->id)
            ->latest('created_at');

        if ($mode && in_array($mode, ['learn_new', 'review', 'quiz'], true)) {
            $query->where('mode', $mode);
        }

        if ($timeframe === '7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($timeframe === '30days') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $sessions = $query->paginate(15)->withQueryString();

        return view('study.history', [
            'sessions' => $sessions,
            'currentMode' => $mode,
            'currentTimeframe' => $timeframe,
        ]);
    }
}

