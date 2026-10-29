@extends('layouts.app')

@section('title', 'Tổng quan quản trị | ' . config('app.name'))

@section('content')
<div class="space-y-8">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                    Khu vực quản trị hệ thống
                </span>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                    Bảng điều khiển Quản trị viên
                </h1>
                <p class="mt-2 text-sm text-slate-600">
                    Theo dõi số lượng tài khoản và thông tin vận hành tổng quan của ứng dụng PolyVocab.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    &larr; Về Dashboard cá nhân
                </a>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                <div class="text-xs font-medium text-slate-500">Tổng số người dùng</div>
                <div class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['total_users']) }}</div>
                <div class="mt-1 text-xs text-slate-500">Tất cả tài khoản trên hệ thống</div>
            </div>

            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5">
                <div class="text-xs font-medium text-indigo-600">Số lượng thành viên</div>
                <div class="mt-2 text-3xl font-bold tracking-tight text-indigo-900">{{ number_format($stats['total_members']) }}</div>
                <div class="mt-1 text-xs text-indigo-600/80">Vai trò người học (member)</div>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-5">
                <div class="text-xs font-medium text-amber-700">Số lượng quản trị viên</div>
                <div class="mt-2 text-3xl font-bold tracking-tight text-amber-900">{{ number_format($stats['total_admins']) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Vai trò quản trị (admin)</div>
            </div>
        </div>
    </div>

    <!-- Danh sách người dùng gần đây -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Danh sách tài khoản mới nhất</h2>
                <p class="text-xs text-slate-500 mt-1">Chỉ hiển thị thông tin công khai cơ bản của tài khoản người dùng</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-700 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 rounded-l-xl">ID</th>
                        <th class="px-4 py-3">Họ và tên</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Vai trò</th>
                        <th class="px-4 py-3 rounded-r-xl">Ngày đăng ký</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentUsers as $user)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-4 py-3 font-mono text-xs text-slate-400">#{{ $user->id }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @if ($user->role === \App\Enums\UserRole::ADMIN)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                        Quản trị viên
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                        Thành viên
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">
                                Chưa có tài khoản nào được tạo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
