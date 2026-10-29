@extends('layouts.app')

@section('title', 'Trang chủ | ' . config('app.name'))

@section('content')
<section class="rounded-3xl border border-slate-200 bg-white p-8 md:p-12 shadow-sm">
    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
        Học từ vựng đa ngôn ngữ
    </span>
    <h1 class="mt-4 text-4xl sm:text-5xl font-bold tracking-tight text-slate-900 leading-tight">
        Mỗi ngày một chút,<br class="hidden sm:inline"> tích lũy thật nhiều.
    </h1>
    <p class="mt-4 max-w-2xl text-base sm:text-lg leading-relaxed text-slate-600">
        Tạo bộ từ của riêng bạn, luyện tập bằng thẻ từ thông minh (Spaced Repetition) và theo dõi tiến độ học tập mỗi ngày.
    </p>

    <div class="mt-8 flex flex-wrap items-center gap-4">
        @auth
            <a href="{{ route('dashboard') }}"
               class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                Đi tới bảng điều khiển &rarr;
            </a>
        @else
            <a href="{{ route('register') }}"
               class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                Bắt đầu học ngay miễn phí
            </a>
            <a href="{{ route('login') }}"
               class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                Đăng nhập
            </a>
        @endauth
    </div>

    <div class="mt-10 rounded-2xl bg-indigo-50/70 border border-indigo-100 p-4 text-sm text-indigo-900 flex items-center space-x-3">
        <svg class="w-5 h-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Hệ thống tài khoản và phân quyền cá nhân đã sẵn sàng hoạt động.</span>
    </div>
</section>
@endsection
