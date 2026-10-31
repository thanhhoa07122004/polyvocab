@extends('layouts.app')

@section('title', 'Trang chủ | ' . config('app.name'))

@section('content')
@php
    $supportedLanguages = collect();
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('languages')) {
            $supportedLanguages = \App\Models\Language::getActiveCached();
        }
    } catch (\Throwable $e) {
        $supportedLanguages = collect();
    }
@endphp

<div class="space-y-16 sm:space-y-20 lg:space-y-24 py-2 sm:py-4">

    {{-- =========================================================================
         1. PHẦN GIỚI THIỆU ĐẦU TRANG (HERO SECTION - 2 CỘT DESKTOP)
         ========================================================================= --}}
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
        {{-- Cột trái (7 cột trên Desktop) --}}
        <div class="lg:col-span-7 space-y-5 sm:space-y-6 text-left">
            <div class="inline-flex items-center space-x-2 rounded-full bg-indigo-50 px-3.5 py-1 text-xs font-bold text-indigo-700 border border-indigo-100">
                <span class="inline-block w-2 h-2 rounded-full bg-indigo-600"></span>
                <span>Học từ vựng đa ngôn ngữ</span>
            </div>

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 leading-[1.18]">
                Mỗi ngày một chút,<br class="hidden sm:inline">
                vốn từ thêm phong phú.
            </h1>

            <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-xl">
                Học từ mới, ghi lại những từ còn khó và luyện tập theo cách bạn thích. Bắt đầu từ một bộ từ phù hợp với mình.
            </p>

            {{-- Nhóm nút hành động chính --}}
            <div class="pt-2 flex flex-col sm:flex-row sm:items-center gap-3.5">
                @auth
                    <a href="{{ route('decks.index') }}"
                       class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Bộ từ của tôi
                    </a>
                    <a href="{{ route('explore') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                        <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Khám phá bộ từ
                    </a>
                    <a href="{{ route('study.practice.index') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-purple-200 bg-purple-50/70 px-5 py-3.5 text-sm font-semibold text-purple-800 shadow-2xs hover:bg-purple-100 transition">
                        <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Vào luyện tập
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                        Tạo tài khoản
                        <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                    <a href="{{ route('explore') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                        Khám phá bộ từ
                    </a>
                @endauth
            </div>

            @guest
                <div class="text-xs text-slate-500">
                    Đã có tài khoản?
                    <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800 underline ml-1">
                        Đăng nhập
                    </a>
                </div>
            @endguest
        </div>

        {{-- Cột phải (5 cột trên Desktop): Cụm thẻ xoay tương tác đa ngôn ngữ (Card Swap Stack) --}}
        <div class="lg:col-span-5 relative">
            <div class="relative mx-auto max-w-sm sm:max-w-md">

                {{-- Khung chứa 3D Card Stack --}}
                <div id="heroCardStack" class="hero-card-stack h-[330px] sm:h-[340px]" tabindex="0" role="region" aria-label="Thẻ từ vựng đa ngôn ngữ xoay tương tác">

                    {{-- Thẻ 1: Tiếng Anh (English) --}}
                    <div class="hero-swap-card rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 flex flex-col justify-between select-none shadow-sm"
                         data-card-index="0"
                         data-slot="0">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 border border-indigo-100/80 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                    Tiếng Anh &rarr; Tiếng Việt
                                </span>
                                <span class="text-[10px] tracking-wider uppercase font-semibold text-slate-400 bg-slate-100/80 px-2 py-0.5 rounded-full">
                                    Minh họa
                                </span>
                            </div>

                            <div class="text-left space-y-1">
                                <div class="flex items-baseline space-x-2.5">
                                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight font-serif">Hello</span>
                                    <span class="text-xs sm:text-sm font-mono text-slate-400">/həˈləʊ/</span>
                                </div>
                                <p class="text-sm font-medium text-indigo-700 pt-0.5">
                                    Xin chào, lời chào thân mật khi gặp mặt.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50/80 p-3 sm:p-3.5 border border-slate-100 text-xs space-y-1 my-2">
                            <p class="font-medium text-slate-800 leading-snug">"Hello! Nice to meet you."</p>
                            <p class="text-slate-500 italic leading-snug">(Xin chào! Rất vui được gặp bạn.)</p>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] text-slate-400">
                            <span class="inline-flex items-center space-x-1.5 text-emerald-600 font-semibold">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Đã ghi nhớ</span>
                            </span>
                            <span class="text-slate-400">Chạm thẻ để đổi từ</span>
                        </div>
                    </div>

                    {{-- Thẻ 2: Tiếng Nhật (日本語) --}}
                    <div class="hero-swap-card rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 flex flex-col justify-between select-none shadow-sm"
                         data-card-index="1"
                         data-slot="1">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <span class="inline-flex items-center rounded-full bg-rose-50 border border-rose-100/80 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                    Tiếng Nhật &rarr; Tiếng Việt
                                </span>
                                <span class="text-[10px] tracking-wider uppercase font-semibold text-slate-400 bg-slate-100/80 px-2 py-0.5 rounded-full">
                                    Minh họa
                                </span>
                            </div>

                            <div class="text-left space-y-1">
                                <div class="flex items-baseline space-x-2.5">
                                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight">こんにちは</span>
                                    <span class="text-xs sm:text-sm font-mono text-slate-400">[Konnichiwa]</span>
                                </div>
                                <p class="text-sm font-medium text-rose-700 pt-0.5">
                                    Xin chào, lời chào ban ngày lịch sự.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50/80 p-3 sm:p-3.5 border border-slate-100 text-xs space-y-1 my-2">
                            <p class="font-medium text-slate-800 leading-snug">"こんにちは、今日はお元気ですか。"</p>
                            <p class="text-slate-500 italic leading-snug">(Xin chào, hôm nay bạn có khỏe không?)</p>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] text-slate-400">
                            <span class="inline-flex items-center space-x-1.5 text-emerald-600 font-semibold">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Đã ghi nhớ</span>
                            </span>
                            <span class="text-slate-400">Chạm thẻ để đổi từ</span>
                        </div>
                    </div>

                    {{-- Thẻ 3: Tiếng Trung (中文) --}}
                    <div class="hero-swap-card rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 flex flex-col justify-between select-none shadow-sm"
                         data-card-index="2"
                         data-slot="2">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <span class="inline-flex items-center rounded-full bg-amber-50 border border-amber-100/80 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                    Tiếng Trung &rarr; Tiếng Việt
                                </span>
                                <span class="text-[10px] tracking-wider uppercase font-semibold text-slate-400 bg-slate-100/80 px-2 py-0.5 rounded-full">
                                    Minh họa
                                </span>
                            </div>

                            <div class="text-left space-y-1">
                                <div class="flex items-baseline space-x-2.5">
                                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight">你好</span>
                                    <span class="text-xs sm:text-sm font-mono text-slate-400">[Nǐ hǎo]</span>
                                </div>
                                <p class="text-sm font-medium text-amber-800 pt-0.5">
                                    Chào bạn, lời chào phổ biến chúc bình an.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50/80 p-3 sm:p-3.5 border border-slate-100 text-xs space-y-1 my-2">
                            <p class="font-medium text-slate-800 leading-snug">"你好！很高兴认识你。"</p>
                            <p class="text-slate-500 italic leading-snug">(Xin chào! Rất vui được quen biết bạn.)</p>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] text-slate-400">
                            <span class="inline-flex items-center space-x-1.5 text-emerald-600 font-semibold">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Đã ghi nhớ</span>
                            </span>
                            <span class="text-slate-400">Chạm thẻ để đổi từ</span>
                        </div>
                    </div>

                    {{-- Thẻ 4: Tiếng Hàn (한국어) --}}
                    <div class="hero-swap-card rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 flex flex-col justify-between select-none shadow-sm"
                         data-card-index="3"
                         data-slot="3">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <span class="inline-flex items-center rounded-full bg-purple-50 border border-purple-100/80 px-2.5 py-0.5 text-xs font-semibold text-purple-700">
                                    Tiếng Hàn &rarr; Tiếng Việt
                                </span>
                                <span class="text-[10px] tracking-wider uppercase font-semibold text-slate-400 bg-slate-100/80 px-2 py-0.5 rounded-full">
                                    Minh họa
                                </span>
                            </div>

                            <div class="text-left space-y-1">
                                <div class="flex items-baseline space-x-2.5">
                                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight">안녕하세요</span>
                                    <span class="text-xs sm:text-sm font-mono text-slate-400">[Annyeonghaseyo]</span>
                                </div>
                                <p class="text-sm font-medium text-purple-700 pt-0.5">
                                    Xin chào, lời chào kính ngữ thân thiện.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50/80 p-3 sm:p-3.5 border border-slate-100 text-xs space-y-1 my-2">
                            <p class="font-medium text-slate-800 leading-snug">"안녕하세요! 만나서 반갑습니다."</p>
                            <p class="text-slate-500 italic leading-snug">(Xin chào! Rất hân hạnh được gặp bạn.)</p>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] text-slate-400">
                            <span class="inline-flex items-center space-x-1.5 text-emerald-600 font-semibold">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Đã ghi nhớ</span>
                            </span>
                            <span class="text-slate-400">Chạm thẻ để đổi từ</span>
                        </div>
                    </div>

                    {{-- Thẻ 5: Tiếng Pháp (Français) --}}
                    <div class="hero-swap-card rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 flex flex-col justify-between select-none shadow-sm"
                         data-card-index="4"
                         data-slot="4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <span class="inline-flex items-center rounded-full bg-sky-50 border border-sky-100/80 px-2.5 py-0.5 text-xs font-semibold text-sky-700">
                                    Tiếng Pháp &rarr; Tiếng Việt
                                </span>
                                <span class="text-[10px] tracking-wider uppercase font-semibold text-slate-400 bg-slate-100/80 px-2 py-0.5 rounded-full">
                                    Minh họa
                                </span>
                            </div>

                            <div class="text-left space-y-1">
                                <div class="flex items-baseline space-x-2.5">
                                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight font-serif">Bonjour</span>
                                    <span class="text-xs sm:text-sm font-mono text-slate-400">/bɔ̃.ʒuʁ/</span>
                                </div>
                                <p class="text-sm font-medium text-sky-700 pt-0.5">
                                    Xin chào, chúc một ngày tốt lành.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50/80 p-3 sm:p-3.5 border border-slate-100 text-xs space-y-1 my-2">
                            <p class="font-medium text-slate-800 leading-snug">"Bonjour, comment allez-vous ?"</p>
                            <p class="text-slate-500 italic leading-snug">(Xin chào, bạn có khỏe không?)</p>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] text-slate-400">
                            <span class="inline-flex items-center space-x-1.5 text-emerald-600 font-semibold">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Đã ghi nhớ</span>
                            </span>
                            <span class="text-slate-400">Chạm thẻ để đổi từ</span>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    {{-- =========================================================================
         2. PHẦN “POLYVOCAB GIÚP BẠN HỌC NHƯ THẾ NÀO?” (3 KHỐI TRỌNG TÂM)
         ========================================================================= --}}
    <section class="space-y-6 sm:space-y-8">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                PolyVocab giúp bạn học như thế nào?
            </h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                Từ lần đầu làm quen đến lúc tự nhớ lại, bạn có thể học, luyện tập và theo dõi kết quả ngay trên một website.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-6">
            {{-- Khối 1: Học từ mới --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-7 shadow-2xs space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">
                        Học từ mới
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Xem từ, nghĩa, cách đọc và ví dụ. Chọn Đã nhớ hoặc Chưa nhớ để ghi lại mức độ nhớ của bạn.
                    </p>
                </div>
                <div class="pt-2 border-t border-slate-100 text-xs font-semibold text-emerald-700 flex items-center">
                    <span>Làm quen từ vựng</span>
                    <span class="ml-1.5">&bull; Từng bước chắc chắn</span>
                </div>
            </div>

            {{-- Khối 2: Luyện tập --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-7 shadow-2xs space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">
                        Luyện tập
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Thực hành bằng nhiều dạng bài như chọn nghĩa, ghép cặp, nghe và viết. Chọn cách luyện phù hợp với nội dung đang học.
                    </p>
                </div>
                <div class="pt-2 border-t border-slate-100 text-xs font-semibold text-purple-700 flex items-center">
                    <span>Đa dạng bài tập</span>
                    <span class="ml-1.5">&bull; Tự do chọn dạng</span>
                </div>
            </div>

            {{-- Khối 3: Ôn tập --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-7 shadow-2xs space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">
                        Ôn tập
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Quay lại những nội dung cần ôn và dành thêm thời gian cho những từ còn khó.
                    </p>
                </div>
                <div class="pt-2 border-t border-slate-100 text-xs font-semibold text-amber-800 flex items-center">
                    <span>Lặp lại ngắt quãng</span>
                    <span class="ml-1.5">&bull; Củng cố dài hạn</span>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         3. PHẦN “MỘT BỘ TỪ, NHIỀU CÁCH HỌC” (8 DẠNG BÀI - VÙNG MINH HỌA TƯƠNG TÁC)
         ========================================================================= --}}
    <section class="space-y-6 sm:space-y-8" id="exercise-showcase">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Một bộ từ, nhiều cách học
            </h2>
            <p class="text-sm sm:text-base text-slate-600">
                Linh hoạt lựa chọn cách luyện nhớ phù hợp với bạn. Bấm vào từng dạng bên dưới để xem cách bài tập hoạt động.
            </p>
        </div>

        {{-- Bố cục: Danh sách dạng bài để chọn + Một vùng minh họa trực quan --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- Cột chọn dạng bài (5 cột desktop, danh sách cuộn mượt hoặc xếp gọn) --}}
            <div class="lg:col-span-5 space-y-2" role="tablist" aria-label="Danh sách các dạng bài luyện tập">
                {{-- 1. Thẻ ghi nhớ --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-indigo-600 bg-indigo-50/70 text-indigo-700 shadow-2xs font-semibold"
                        data-target="panel-flashcard"
                        role="tab"
                        aria-selected="true"
                        aria-controls="panel-flashcard"
                        id="tab-flashcard">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-white/80 flex items-center justify-center shrink-0 text-base shadow-2xs">🃏</span>
                        <div>
                            <span class="block text-sm font-bold">Thẻ ghi nhớ</span>
                            <span class="block text-2xs opacity-80">Tự nhớ rồi lật thẻ xem đáp án</span>
                        </div>
                    </div>
                    <span class="text-xs transition-transform transform group-hover:translate-x-1">&rarr;</span>
                </button>

                {{-- 2. Chọn nghĩa --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium"
                        data-target="panel-multiple-choice"
                        role="tab"
                        aria-selected="false"
                        aria-controls="panel-multiple-choice"
                        id="tab-multiple-choice">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-base">📝</span>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Chọn nghĩa</span>
                            <span class="block text-2xs text-slate-500">Nhìn từ và chọn nghĩa phù hợp</span>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400">&rarr;</span>
                </button>

                {{-- 3. Chọn từ --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium"
                        data-target="panel-reverse-choice"
                        role="tab"
                        aria-selected="false"
                        aria-controls="panel-reverse-choice"
                        id="tab-reverse-choice">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-base">🔍</span>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Chọn từ</span>
                            <span class="block text-2xs text-slate-500">Nhìn nghĩa và chọn từ tương ứng</span>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400">&rarr;</span>
                </button>

                {{-- 4. Ghép cặp --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium"
                        data-target="panel-matching-pairs"
                        role="tab"
                        aria-selected="false"
                        aria-controls="panel-matching-pairs"
                        id="tab-matching-pairs">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-base">🔗</span>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Ghép cặp</span>
                            <span class="block text-2xs text-slate-500">Nối từ với nghĩa tương ứng</span>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400">&rarr;</span>
                </button>

                {{-- 5. Nghe và chọn --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium"
                        data-target="panel-listen-choice"
                        role="tab"
                        aria-selected="false"
                        aria-controls="panel-listen-choice"
                        id="tab-listen-choice">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-base">🎧</span>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Nghe và chọn</span>
                            <span class="block text-2xs text-slate-500">Nghe phát âm rồi chọn đáp án</span>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400">&rarr;</span>
                </button>

                {{-- 6. Nghe và viết --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium"
                        data-target="panel-dictation"
                        role="tab"
                        aria-selected="false"
                        aria-controls="panel-dictation"
                        id="tab-dictation">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-base">✍️</span>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Nghe và viết</span>
                            <span class="block text-2xs text-slate-500">Nghe phát âm rồi gõ lại từ</span>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400">&rarr;</span>
                </button>

                {{-- 7. Điền từ --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium"
                        data-target="panel-fill-in-blank"
                        role="tab"
                        aria-selected="false"
                        aria-controls="panel-fill-in-blank"
                        id="tab-fill-in-blank">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-base">✏️</span>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Điền từ</span>
                            <span class="block text-2xs text-slate-500">Điền phần còn thiếu trong câu</span>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400">&rarr;</span>
                </button>

                {{-- 8. Sắp xếp chữ --}}
                <button type="button"
                        class="exercise-tab-btn w-full text-left p-3 sm:p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium"
                        data-target="panel-unscramble"
                        role="tab"
                        aria-selected="false"
                        aria-controls="panel-unscramble"
                        id="tab-unscramble">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-base">🧩</span>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Sắp xếp chữ</span>
                            <span class="block text-2xs text-slate-500">Ghép các mảnh chữ tạo đáp án</span>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400">&rarr;</span>
                </button>
            </div>

            {{-- Cột vùng minh họa (7 cột desktop) --}}
            <div class="lg:col-span-7 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">

                {{-- Đầu khung minh họa: Tiêu đề + Huy hiệu Ví dụ minh họa --}}
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 border border-amber-200">
                            Ví dụ minh họa
                        </span>
                    </div>
                    <span class="text-xs text-slate-400">
                        Chỉ xem mẫu &bull; Không tính điểm
                    </span>
                </div>

                {{-- 1. Panel: Thẻ ghi nhớ --}}
                <div id="panel-flashcard" class="exercise-preview-panel space-y-5" role="tabpanel" aria-labelledby="tab-flashcard">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Thẻ ghi nhớ (Flashcard)</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Nhìn từ vựng ở mặt trước, tự gợi nhớ nghĩa trong đầu rồi bấm lật thẻ để kiểm tra lại đáp án.
                        </p>
                    </div>

                    {{-- Thẻ ghi nhớ 3D lật 2 mặt tương tác thực tế --}}
                    <div class="flashcard-3d-scene max-w-sm mx-auto my-2 select-none">
                        <div id="homeDemoFlashcard"
                             class="flashcard-3d-card min-h-[220px] h-[230px]"
                             onclick="toggleHomeDemoFlashcard(event)"
                             role="button"
                             tabindex="0"
                             aria-label="Thẻ ghi nhớ minh họa - Bấm để lật xem đáp án">
                            
                            <!-- Mặt trước thẻ -->
                            <div class="flashcard-3d-face flashcard-3d-front rounded-3xl border border-indigo-200/90 bg-white p-5 text-center shadow-xs hover:border-indigo-400 transition-colors duration-200">
                                <div class="flex items-center justify-between w-full">
                                    <span class="text-2xs font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 border border-indigo-100/80 px-2.5 py-0.5 rounded-full">
                                        Mặt trước (Từ vựng)
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">EN &rarr; VI</span>
                                </div>
                                <div class="my-auto py-2 space-y-1">
                                    <div class="text-3xl font-extrabold text-slate-900 font-serif tracking-tight">Apple</div>
                                    <div class="text-xs font-mono text-slate-400">/ˈæp.əl/</div>
                                </div>
                                <div class="inline-flex items-center justify-center space-x-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50/70 py-1 px-3 rounded-full border border-indigo-100 shadow-2xs self-center">
                                    <span>🔄 Bấm thẻ để lật xem nghĩa</span>
                                </div>
                            </div>

                            <!-- Mặt sau thẻ -->
                            <div class="flashcard-3d-face flashcard-3d-back rounded-3xl border border-emerald-200/90 bg-white p-5 text-center shadow-xs hover:border-emerald-400 transition-colors duration-200">
                                <div class="flex items-center justify-between w-full">
                                    <span class="text-2xs font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 border border-emerald-100/80 px-2.5 py-0.5 rounded-full">
                                        Mặt sau (Giải nghĩa)
                                    </span>
                                    <span class="text-[10px] text-emerald-600 font-semibold">✓ Đáp án</span>
                                </div>
                                <div class="my-auto py-2 space-y-1">
                                    <div class="text-2xl font-bold text-slate-900 tracking-tight">Quả táo</div>
                                    <p class="text-xs text-slate-600 italic">"She is eating a red apple."</p>
                                </div>
                                <div class="inline-flex items-center justify-center space-x-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50/70 py-1 px-3 rounded-full border border-emerald-100 shadow-2xs self-center">
                                    <span>↺ Bấm thẻ để lật lại mặt trước</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Bạn chạm hoặc bấm vào thẻ để lật qua lại giữa mặt từ vựng và mặt giải nghĩa.
                    </div>
                </div>

                {{-- 2. Panel: Chọn nghĩa --}}
                <div id="panel-multiple-choice" class="exercise-preview-panel space-y-5 hidden" role="tabpanel" aria-labelledby="tab-multiple-choice">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Chọn nghĩa phù hợp</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Đọc từ vựng được đưa ra và chọn nét nghĩa chính xác nhất trong các phương án.
                        </p>
                    </div>

                    {{-- Mock UI Chọn nghĩa --}}
                    <div class="space-y-3">
                        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 text-center space-y-1">
                            <div class="text-2xl font-bold text-slate-900 font-serif">Window</div>
                            <div class="text-xs font-mono text-slate-500">/ˈwɪn.dəʊ/ &bull; Danh từ</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl border border-emerald-300 bg-emerald-50/70 text-emerald-900 font-bold flex items-center justify-between">
                                <span>A. Cửa sổ</span>
                                <span class="text-emerald-700 font-black">✓ Đáp án đúng</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>B. Cửa ra vào</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>C. Bức tường</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>D. Mái nhà</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Nhấn trực tiếp vào ô đáp án tương ứng. Hệ thống sẽ báo đúng/sai ngay lập tức.
                    </div>
                </div>

                {{-- 3. Panel: Chọn từ --}}
                <div id="panel-reverse-choice" class="exercise-preview-panel space-y-5 hidden" role="tabpanel" aria-labelledby="tab-reverse-choice">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Chọn từ tương ứng</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Đọc nghĩa tiếng Việt và chọn từ vựng tương ứng trong ngôn ngữ đang học.
                        </p>
                    </div>

                    {{-- Mock UI Chọn từ --}}
                    <div class="space-y-3">
                        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 text-center space-y-1">
                            <div class="text-xl font-bold text-slate-900">Quả táo</div>
                            <div class="text-xs text-slate-500">Trái cây tròn có vị ngọt hoặc chua nhẹ</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl border border-emerald-300 bg-emerald-50/70 text-emerald-900 font-bold flex items-center justify-between">
                                <span>A. Apple</span>
                                <span class="text-emerald-700 font-black">✓ Đáp án đúng</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>B. Orange</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>C. Banana</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>D. Strawberry</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Luyện phản xạ bật từ vựng mục tiêu từ nghĩa tiếng Việt.
                    </div>
                </div>

                {{-- 4. Panel: Ghép cặp --}}
                <div id="panel-matching-pairs" class="exercise-preview-panel space-y-5 hidden" role="tabpanel" aria-labelledby="tab-matching-pairs">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Ghép cặp từ và nghĩa</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Nối từng từ vựng ở cột bên trái với nét nghĩa tương ứng ở cột bên phải.
                        </p>
                    </div>

                    {{-- Mock UI Ghép cặp --}}
                    <div class="grid grid-cols-2 gap-3 text-xs select-none">
                        <div class="space-y-2">
                            <div class="p-3 rounded-xl border border-indigo-300 bg-indigo-50/70 text-indigo-900 font-bold flex items-center justify-between">
                                <span>Hello</span>
                                <span class="text-2xs text-indigo-600">Đang chọn</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium">
                                <span>Book</span>
                            </div>
                            <div class="p-3 rounded-xl border border-emerald-300 bg-emerald-50/60 text-emerald-800 font-semibold flex items-center justify-between">
                                <span>Apple</span>
                                <span>✓</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="p-3 rounded-xl border border-emerald-300 bg-emerald-50/60 text-emerald-800 font-semibold flex items-center justify-between">
                                <span>Quả táo</span>
                                <span>✓</span>
                            </div>
                            <div class="p-3 rounded-xl border border-indigo-300 bg-indigo-50/70 text-indigo-900 font-bold">
                                <span>Xin chào</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium">
                                <span>Quyển sách</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Chọn một mục bên trái, sau đó chọn mục tương ứng bên phải để tạo thành cặp nối.
                    </div>
                </div>

                {{-- 5. Panel: Nghe và chọn --}}
                <div id="panel-listen-choice" class="exercise-preview-panel space-y-5 hidden" role="tabpanel" aria-labelledby="tab-listen-choice">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Nghe và chọn đáp án</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Lắng nghe cách phát âm của từ và chọn từ đúng trong các phương án gây nhiễu.
                        </p>
                    </div>

                    {{-- Mock UI Nghe và chọn --}}
                    <div class="space-y-3">
                        <div class="rounded-2xl bg-indigo-50/40 p-4 border border-indigo-100 text-center space-y-2">
                            <div class="inline-flex items-center space-x-2 text-indigo-700 font-semibold text-sm">
                                <span class="text-lg">🔊</span>
                                <span>Mẫu phát âm: [Audio từ vựng]</span>
                            </div>
                            <p class="text-2xs text-slate-500">Bấm nghe lại nếu cần trước khi chọn đáp án</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl border border-emerald-300 bg-emerald-50/70 text-emerald-900 font-bold flex items-center justify-between">
                                <span>A. Apple</span>
                                <span class="text-emerald-700 font-black">✓</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>B. Apply</span>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white text-slate-700">
                                <span>C. April</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Rèn luyện kỹ năng nhận diện âm thanh mà không phụ thuộc vào văn bản có sẵn.
                    </div>
                </div>

                {{-- 6. Panel: Nghe và viết --}}
                <div id="panel-dictation" class="exercise-preview-panel space-y-5 hidden" role="tabpanel" aria-labelledby="tab-dictation">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Nghe và viết (Chính tả)</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Lắng nghe phát âm và gõ chính xác lại từ vựng vào ô nhập liệu.
                        </p>
                    </div>

                    {{-- Mock UI Nghe và viết --}}
                    <div class="space-y-3">
                        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 text-center space-y-1">
                            <div class="inline-flex items-center space-x-2 text-indigo-700 font-semibold text-sm">
                                <span class="text-lg">🔊</span>
                                <span>Mẫu phát âm: [Audio từ vựng]</span>
                            </div>
                            <p class="text-2xs text-slate-500">Nghe cẩn thận từng âm tiết của từ</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-2xs">
                                Hello
                            </div>
                            <span class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs">
                                Kiểm tra
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Gõ từ từ bàn phím và nhấn Enter hoặc nút Kiểm tra để đối chiếu đáp án.
                    </div>
                </div>

                {{-- 7. Panel: Điền từ --}}
                <div id="panel-fill-in-blank" class="exercise-preview-panel space-y-5 hidden" role="tabpanel" aria-labelledby="tab-fill-in-blank">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Điền từ vào câu ví dụ</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Đọc câu ví dụ ngữ cảnh và điền từ thích hợp vào chỗ trống.
                        </p>
                    </div>

                    {{-- Mock UI Điền từ --}}
                    <div class="space-y-3">
                        <div class="rounded-2xl bg-slate-50 p-5 border border-slate-100 space-y-2">
                            <p class="text-sm font-medium text-slate-800 leading-relaxed">
                                "I eat an <span class="inline-block px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold border border-indigo-300">apple</span> every morning."
                            </p>
                            <p class="text-xs text-slate-500 italic">
                                (Tôi ăn một quả táo mỗi buổi sáng.)
                            </p>
                        </div>

                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-slate-500">Gợi ý từ loại:</span>
                            <span class="font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">Danh từ &bull; Quả táo</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Hiểu từ trong ngữ cảnh câu thực tế, rèn luyện cách sử dụng từ ngữ chính xác.
                    </div>
                </div>

                {{-- 8. Panel: Sắp xếp chữ --}}
                <div id="panel-unscramble" class="exercise-preview-panel space-y-5 hidden" role="tabpanel" aria-labelledby="tab-unscramble">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-900">Sắp xếp chữ cái</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Yêu cầu: Nhìn nghĩa và chọn các mảnh chữ cái theo đúng thứ tự để tạo nên từ hoàn chỉnh.
                        </p>
                    </div>

                    {{-- Mock UI Sắp xếp chữ --}}
                    <div class="space-y-4">
                        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 text-center space-y-1">
                            <div class="text-base font-bold text-slate-900">Nghĩa: Quả táo</div>
                        </div>

                        {{-- Ô kết quả đang ghép --}}
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span class="w-10 h-10 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-900 font-bold text-base flex items-center justify-center">a</span>
                            <span class="w-10 h-10 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-900 font-bold text-base flex items-center justify-center">p</span>
                            <span class="w-10 h-10 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-900 font-bold text-base flex items-center justify-center">p</span>
                            <span class="w-10 h-10 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-900 font-bold text-base flex items-center justify-center">l</span>
                            <span class="w-10 h-10 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-900 font-bold text-base flex items-center justify-center">e</span>
                        </div>

                        {{-- Mảnh chữ để chọn --}}
                        <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-center gap-2">
                            <span class="text-2xs text-slate-400 w-full text-center">Các mảnh chữ cái đã ghép thành công</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100 text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">Thao tác khi học:</strong> Chạm hoặc bấm vào từng ký tự theo thứ tự đúng để ghép thành từ.
                    </div>
                </div>

                <p class="text-center text-2xs text-slate-400 italic">
                    * Các dạng bài có thể sử dụng tùy thuộc vào nội dung và dữ liệu audio / ví dụ có trong từng bộ từ cụ thể.
                </p>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         4. PHẦN “HỌC NHIỀU NGÔN NGỮ THEO CÁCH CỦA BẠN”
         ========================================================================= --}}
    <section class="space-y-6 sm:space-y-8">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Học nhiều ngôn ngữ theo cách của bạn
            </h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                Chọn ngôn ngữ muốn học và bộ từ phù hợp. Bạn có thể chuyển đổi giữa các ngôn ngữ mà vẫn giữ tiến độ học riêng.
            </p>
        </div>

        {{-- 3 Điểm cốt lõi --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xs space-y-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    🌐
                </div>
                <h3 class="text-base font-bold text-slate-900">
                    Ngôn ngữ học & Giải nghĩa
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Linh hoạt chọn cặp ngôn ngữ. Học tiếng Anh bằng tiếng Việt, hoặc học tiếng Nhật bằng tiếng Anh tùy theo mục tiêu.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xs space-y-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                    📖
                </div>
                <h3 class="text-base font-bold text-slate-900">
                    Học trong ngữ cảnh
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Học từ hoặc cụm từ kèm theo câu ví dụ thực tế và bản dịch giúp hiểu sâu và áp dụng tự nhiên.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xs space-y-2.5">
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">
                    🗣️
                </div>
                <h3 class="text-base font-bold text-slate-900">
                    Cách đọc và phát âm
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Xem phiên âm quốc tế (IPA) và nghe phát âm chuẩn khi nội dung bộ từ có hỗ trợ.
                </p>
            </div>
        </div>

        {{-- Danh mục các ngôn ngữ hệ thống đang hỗ trợ (lấy từ dữ liệu thực tế) --}}
        @if ($supportedLanguages->isNotEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-7 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Các ngôn ngữ đang được hỗ trợ trong hệ thống</h3>
                        <p class="text-xs text-slate-500">Bạn có thể tạo hoặc tìm kiếm bộ từ vựng thuộc các ngôn ngữ này</p>
                    </div>
                    <a href="{{ route('explore') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition shrink-0">
                        Xem tất cả bộ từ &rarr;
                    </a>
                </div>

                <div class="flex flex-wrap gap-2.5 pt-1">
                    @foreach ($supportedLanguages as $lang)
                        <span class="inline-flex items-center space-x-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-800">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <span>{{ $lang->name }}</span>
                            @if (!empty($lang->native_name) && $lang->native_name !== $lang->name)
                                <span class="text-slate-400 font-normal">({{ $lang->native_name }})</span>
                            @endif
                        </span>
                    @endforeach
                </div>

                <p class="text-2xs text-slate-400 italic pt-1">
                    * Lưu ý: Khả dụng về âm thanh phát âm, phiên âm và dạng bài tập phụ thuộc vào dữ liệu chi tiết của từng bộ từ.
                </p>
            </div>
        @endif
    </section>

    {{-- =========================================================================
         5. PHẦN “BỘ TỪ CỦA BẠN, NỘI DUNG BẠN CẦN” & “BIẾT MÌNH CẦN HỌC THÊM ĐIỀU GÌ”
         ========================================================================= --}}
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- Cột trái (6 cột desktop): Bộ từ của bạn, nội dung bạn cần --}}
        <div class="lg:col-span-6 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">
            <div class="space-y-2">
                <div class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700">
                    Nội dung cá nhân
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">
                    Bộ từ của bạn, nội dung bạn cần
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Tạo bộ từ theo bài học, chủ đề hoặc mục tiêu cá nhân để dễ tìm lại và luyện tập.
                </p>
            </div>

            {{-- 4 Ví dụ mục đích sử dụng --}}
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                    <span class="font-bold text-slate-800 block">💬 Từ vựng giao tiếp</span>
                    <span class="text-2xs text-slate-500 mt-0.5 block">Hội thoại hàng ngày, du lịch</span>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                    <span class="font-bold text-slate-800 block">📚 Nội dung trên lớp</span>
                    <span class="text-2xs text-slate-500 mt-0.5 block">Theo giáo trình, bài kiểm tra</span>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                    <span class="font-bold text-slate-800 block">💼 Chuyên ngành</span>
                    <span class="text-2xs text-slate-500 mt-0.5 block">Công nghệ, y tế, kinh tế</span>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                    <span class="font-bold text-slate-800 block">🎯 Ôn thi trọng tâm</span>
                    <span class="text-2xs text-slate-500 mt-0.5 block">Chuẩn bị trước kỳ thi</span>
                </div>
            </div>

            {{-- Danh sách các tính năng --}}
            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600">
                <li class="flex items-start space-x-2">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>Tạo và sắp xếp nội dung trong bộ từ theo cách của bạn.</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>Thêm nhiều nét nghĩa và ví dụ cụ thể cho mỗi từ vựng.</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>Chọn chế độ riêng tư hoặc chia sẻ công khai cho cộng đồng.</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>Khám phá các bộ từ công khai của người khác để học ngay.</span>
                </li>
            </ul>

            {{-- Nút hành động --}}
            <div class="pt-2 flex flex-wrap items-center gap-3">
                @auth
                    <a href="{{ route('decks.create') }}"
                       class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition">
                        + Tạo bộ từ mới
                    </a>
                    <a href="{{ route('decks.index') }}"
                       class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Bộ từ của tôi
                    </a>
                @else
                    <a href="{{ route('explore') }}"
                       class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition">
                        Khám phá bộ từ
                    </a>
                @endauth
            </div>
        </div>

        {{-- Cột phải (6 cột desktop): Biết mình cần học thêm điều gì --}}
        <div class="lg:col-span-6 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">
            <div class="space-y-2">
                <div class="inline-flex items-center rounded-md bg-purple-50 px-2.5 py-0.5 text-xs font-bold text-purple-700">
                    Theo dõi tiến trình
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">
                    Biết mình cần học thêm điều gì
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Không chỉ xem đáp án, bạn còn có thể nhận ra những nội dung mình chưa nhớ để quay lại luyện thêm.
                </p>
            </div>

            {{-- 3 Điểm ngắn --}}
            <div class="space-y-3 text-xs sm:text-sm text-slate-600">
                <div class="flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 font-bold text-xs mt-0.5">⭐</span>
                    <div>
                        <strong class="font-semibold text-slate-900 block">Đánh dấu từ khó</strong>
                        <span>Gắn cờ những từ hay quên để hệ thống gom riêng thành danh sách luyện tập tập trung.</span>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 font-bold text-xs mt-0.5">📊</span>
                    <div>
                        <strong class="font-semibold text-slate-900 block">Xem kết quả sau khi luyện</strong>
                        <span>Báo cáo rõ ràng số từ đúng, từ sai và danh sách cần luyện lại ngay sau mỗi lượt học.</span>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 font-bold text-xs mt-0.5">⏱️</span>
                    <div>
                        <strong class="font-semibold text-slate-900 block">Lịch sử và tiến trình học</strong>
                        <span>Lưu giữ lịch sử các phiên học giúp bạn biết mình đã đi được bao xa.</span>
                    </div>
                </div>
            </div>

            {{-- Bảng kết quả minh họa nhỏ --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
                <div class="flex items-center justify-between text-2xs uppercase tracking-wider font-bold text-slate-400 border-b border-slate-200 pb-2">
                    <span>Ví dụ bảng kết quả sau bài luyện</span>
                    <span class="text-indigo-600 font-semibold">Minh họa</span>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-2.5">
                        <span class="text-2xs font-semibold text-emerald-800 block">Đã nhớ</span>
                        <span class="text-lg font-black text-emerald-700">8</span>
                    </div>
                    <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-2.5">
                        <span class="text-2xs font-semibold text-rose-800 block">Chưa nhớ</span>
                        <span class="text-lg font-black text-rose-700">2</span>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-2.5">
                        <span class="text-2xs font-semibold text-amber-800 block">Cần luyện thêm</span>
                        <span class="text-lg font-black text-amber-700">2</span>
                    </div>
                </div>

                <p class="text-2xs text-slate-400 italic text-center">
                    * Đây là bảng minh họa giao diện kết quả, không phải dữ liệu cá nhân của bạn.
                </p>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         6. PHẦN “BẮT ĐẦU NHƯ THẾ NÀO?” (3 BƯỚC ĐƠN GIẢN)
         ========================================================================= --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-7 sm:p-10 shadow-2xs space-y-8">
        <div class="text-center max-w-xl mx-auto space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Bắt đầu như thế nào?
            </h2>
            <p class="text-sm text-slate-600">
                Chỉ 3 bước đơn giản để bắt đầu ghi nhớ từ vựng mỗi ngày.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 relative">
            {{-- Bước 1 --}}
            <div class="space-y-3 relative text-center sm:text-left">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white font-extrabold text-sm flex items-center justify-center mx-auto sm:mx-0 shadow-xs">
                    1
                </div>
                <h3 class="text-base font-bold text-slate-900">
                    Chọn bộ từ
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Khám phá bộ có sẵn hoặc tạo bộ của riêng bạn.
                </p>
            </div>

            {{-- Bước 2 --}}
            <div class="space-y-3 relative text-center sm:text-left">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white font-extrabold text-sm flex items-center justify-center mx-auto sm:mx-0 shadow-xs">
                    2
                </div>
                <h3 class="text-base font-bold text-slate-900">
                    Học từng từ
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Xem nội dung và chọn Đã nhớ hoặc Chưa nhớ.
                </p>
            </div>

            {{-- Bước 3 --}}
            <div class="space-y-3 relative text-center sm:text-left">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white font-extrabold text-sm flex items-center justify-center mx-auto sm:mx-0 shadow-xs">
                    3
                </div>
                <h3 class="text-base font-bold text-slate-900">
                    Luyện và ôn lại
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Thử các dạng bài, xem kết quả và quay lại phần còn khó.
                </p>
            </div>
        </div>

        {{-- Nút điều hướng chân phần bắt đầu --}}
        <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-center gap-3">
            @auth
                <a href="{{ route('decks.index') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                    Mở bộ từ của tôi &rarr;
                </a>
                <a href="{{ route('study.practice.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Vào luyện tập ngay
                </a>
            @else
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                    Tạo tài khoản miễn phí &rarr;
                </a>
                <a href="{{ route('explore') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Khám phá bộ từ ngay
                </a>
            @endauth
        </div>
    </section>

    {{-- =========================================================================
         7. CÂU HỎI THƯỜNG GẶP (ACCORDION DỄ TRUY CẬP)
         ========================================================================= --}}
    <section class="space-y-6 sm:space-y-8 max-w-3xl mx-auto">
        <div class="text-center space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Câu hỏi thường gặp
            </h2>
            <p class="text-sm text-slate-600">
                Những thắc mắc phổ biến khi bạn bắt đầu học từ vựng với PolyVocab.
            </p>
        </div>

        <div class="space-y-3">
            {{-- Câu 1 --}}
            <details class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <summary class="flex items-center justify-between cursor-pointer list-none text-sm sm:text-base font-bold text-slate-900 focus:outline-none">
                    <span>Tôi mới bắt đầu học có dùng được không?</span>
                    <span class="ml-3 shrink-0 text-slate-400 group-open:rotate-180 transition-transform duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <div class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                    Bạn có thể bắt đầu với một bộ từ có nội dung phù hợp và học từng phần theo nhịp của mình.
                </div>
            </details>

            {{-- Câu 2 --}}
            <details class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <summary class="flex items-center justify-between cursor-pointer list-none text-sm sm:text-base font-bold text-slate-900 focus:outline-none">
                    <span>Học từ mới khác luyện tập như thế nào?</span>
                    <span class="ml-3 shrink-0 text-slate-400 group-open:rotate-180 transition-transform duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <div class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                    Học từ mới giúp bạn xem và làm quen với nội dung. Luyện tập yêu cầu bạn nhớ lại qua các dạng bài.
                </div>
            </details>

            {{-- Câu 3 --}}
            <details class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <summary class="flex items-center justify-between cursor-pointer list-none text-sm sm:text-base font-bold text-slate-900 focus:outline-none">
                    <span>Tại sao có dạng bài chưa dùng được?</span>
                    <span class="ml-3 shrink-0 text-slate-400 group-open:rotate-180 transition-transform duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <div class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                    Một số dạng cần âm thanh, ví dụ hoặc dữ liệu phù hợp. Bộ từ chưa có đủ nội dung sẽ chưa dùng được dạng đó.
                </div>
            </details>

            {{-- Câu 4 --}}
            <details class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <summary class="flex items-center justify-between cursor-pointer list-none text-sm sm:text-base font-bold text-slate-900 focus:outline-none">
                    <span>Tôi có thể tạo bộ từ riêng không?</span>
                    <span class="ml-3 shrink-0 text-slate-400 group-open:rotate-180 transition-transform duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <div class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                    Có. Sau khi đăng nhập, bạn vào mục <strong>Bộ từ của tôi</strong> và nhấn nút <strong>+ Thêm bộ từ</strong>. Bạn có thể tự đặt tên, chọn ngôn ngữ học và thêm từng mục từ vựng, nét nghĩa và câu ví dụ theo ý muốn.
                </div>
            </details>

            {{-- Câu 5 --}}
            <details class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <summary class="flex items-center justify-between cursor-pointer list-none text-sm sm:text-base font-bold text-slate-900 focus:outline-none">
                    <span>Tôi có thể chuyển màu giao diện không?</span>
                    <span class="ml-3 shrink-0 text-slate-400 group-open:rotate-180 transition-transform duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <div class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                    Có. Bạn có thể chọn giao diện sáng, tối hoặc các tông pastel trong menu giao diện ở góc trên thanh điều hướng.
                </div>
            </details>
        </div>
    </section>

    {{-- =========================================================================
         8. KHỐI KẾT TRANG (CALL TO ACTION BANNER)
         ========================================================================= --}}
    <section class="rounded-3xl border border-indigo-100 bg-indigo-50/50 p-8 sm:p-12 text-center space-y-4">
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
            Sẵn sàng học thêm những từ mới?
        </h2>
        <p class="text-sm sm:text-base text-slate-600 max-w-md mx-auto">
            Chọn một bộ từ và bắt đầu học theo nhịp độ của bạn ngay hôm nay.
        </p>
        <div class="pt-2 flex flex-wrap items-center justify-center gap-3.5">
            @auth
                <a href="{{ route('decks.index') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                    Mở bộ từ của tôi
                </a>
                <a href="{{ route('explore') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                    Khám phá thêm bộ từ
                </a>
            @else
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                    Tạo tài khoản miễn phí
                </a>
                <a href="{{ route('explore') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                    Khám phá bộ từ
                </a>
            @endauth
        </div>
    </section>

</div>

{{-- Script điều khiển Tab chuyển đổi ví dụ minh họa 8 dạng bài (Hỗ trợ chuột & bàn phím) --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var buttons = document.querySelectorAll('.exercise-tab-btn');
        var panels = document.querySelectorAll('.exercise-preview-panel');

        function switchTab(selectedBtn) {
            var targetId = selectedBtn.getAttribute('data-target');

            // Reset tất cả các nút
            buttons.forEach(function (btn) {
                btn.classList.remove('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-700', 'font-semibold', 'shadow-2xs');
                btn.classList.add('border-slate-200', 'bg-white', 'text-slate-700', 'hover:bg-slate-50', 'font-medium');
                btn.setAttribute('aria-selected', 'false');
                var arrow = btn.querySelector('span:last-child');
                if (arrow) {
                    arrow.classList.remove('text-indigo-600');
                    arrow.classList.add('text-slate-400');
                }
            });

            // Kích hoạt nút được chọn
            selectedBtn.classList.remove('border-slate-200', 'bg-white', 'text-slate-700', 'hover:bg-slate-50', 'font-medium');
            selectedBtn.classList.add('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-700', 'font-semibold', 'shadow-2xs');
            selectedBtn.setAttribute('aria-selected', 'true');
            var selectedArrow = selectedBtn.querySelector('span:last-child');
            if (selectedArrow) {
                selectedArrow.classList.remove('text-slate-400');
                selectedArrow.classList.add('text-indigo-600');
            }

            // Ẩn tất cả panels và hiện panel tương ứng
            panels.forEach(function (panel) {
                if (panel.id === targetId) {
                    panel.classList.remove('hidden');
                } else {
                    panel.classList.add('hidden');
                }
            });
        }

        buttons.forEach(function (btn, index) {
            btn.addEventListener('click', function () {
                switchTab(this);
            });

            // Hỗ trợ bàn phím điều hướng mũi tên lên/xuống
            btn.addEventListener('keydown', function (e) {
                var nextIndex = -1;
                if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextIndex = (index + 1) % buttons.length;
                } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                    e.preventDefault();
                    nextIndex = (index - 1 + buttons.length) % buttons.length;
                }

                if (nextIndex !== -1) {
                    buttons[nextIndex].focus();
                    switchTab(buttons[nextIndex]);
                }
            });
        });

        // =========================================================================
        // ĐIỀU KHIỂN HIỆU ỨNG THẺ GHI NHỚ MINH HỌA (3D FLIP FLASHCARD DEMO)
        // =========================================================================
        function toggleHomeDemoFlashcard(e) {
            if (e) e.stopPropagation();
            var card = document.getElementById('homeDemoFlashcard');
            if (!card) return;
            var isFlipped = card.classList.contains('is-flipped');
            if (!isFlipped) {
                card.classList.remove('flip-to-front');
                void card.offsetWidth; // Force reflow để trigger 3D keyframe
                card.classList.add('flip-to-back', 'is-flipped');
            } else {
                card.classList.remove('flip-to-back');
                void card.offsetWidth; // Force reflow để trigger 3D keyframe
                card.classList.add('flip-to-front');
                card.classList.remove('is-flipped');
            }
        }
        window.toggleHomeDemoFlashcard = toggleHomeDemoFlashcard;

        var homeDemoFlashcard = document.getElementById('homeDemoFlashcard');
        if (homeDemoFlashcard) {
            homeDemoFlashcard.addEventListener('keydown', function (e) {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    toggleHomeDemoFlashcard(e);
                }
            });
        }

        // =========================================================================
        // ĐIỀU KHIỂN HIỆU ỨNG THẺ DI CHUYỂN VÀ XOAY HOÁN ĐỔI 3D (3D CARD SWAP ANIMATION)
        // =========================================================================
        (function () {
            var stack = document.getElementById('heroCardStack');
            if (!stack) return;

            var cards = Array.from(stack.querySelectorAll('.hero-swap-card'));
            var totalCards = cards.length;
            var activeIndex = 0;
            var isAnimating = false;

            function swapCards(targetIndex) {
                if (isAnimating || totalCards <= 1) return;
                isAnimating = true;

                var nextIndex = (typeof targetIndex === 'number')
                    ? (targetIndex + totalCards) % totalCards
                    : (activeIndex + 1) % totalCards;

                if (nextIndex === activeIndex) {
                    isAnimating = false;
                    return;
                }

                var oldCard = cards[activeIndex];
                var newCard = cards[nextIndex];

                // Kích hoạt keyframe animation xoay và văng ra ngoài không gian 3D
                oldCard.classList.remove('card-swing-out', 'card-swing-in');
                newCard.classList.remove('card-swing-out', 'card-swing-in');
                void oldCard.offsetWidth; // Force reflow để trigger animation
                void newCard.offsetWidth;

                oldCard.classList.add('card-swing-out');
                newCard.classList.add('card-swing-in');

                // Đồng thời cho các thẻ còn lại chuyển dịch mượt mà về vị trí mới
                cards.forEach(function (card, i) {
                    if (card !== oldCard && card !== newCard) {
                        var slot = (i - nextIndex + totalCards) % totalCards;
                        card.setAttribute('data-slot', slot);
                    }
                });

                // Khi hết chu trình hoạt ảnh (380ms), cập nhật vị trí slot chính thức và gỡ class hoạt ảnh
                setTimeout(function () {
                    activeIndex = nextIndex;
                    cards.forEach(function (card, i) {
                        var slot = (i - activeIndex + totalCards) % totalCards;
                        card.setAttribute('data-slot', slot);
                        card.classList.remove('card-swing-out', 'card-swing-in');
                    });
                    isAnimating = false;
                }, 380);
            }

            // Bấm vào thẻ để xoay chuyển vị trí
            cards.forEach(function (card, index) {
                card.addEventListener('click', function () {
                    if (isAnimating) return;
                    var currentSlot = parseInt(this.getAttribute('data-slot'), 10);
                    if (currentSlot === 1 || currentSlot === 2) {
                        // Bấm thẻ lót đằng sau -> xoay thẻ đó lên phía trước
                        swapCards(index);
                    } else {
                        // Bấm thẻ chính ở mặt trước -> xoay thẻ tiếp theo
                        swapCards();
                    }
                });
            });

            // Hỗ trợ bàn phím khi focus vào stack: Space, Enter, ArrowRight để xoay
            stack.addEventListener('keydown', function (e) {
                if (e.key === ' ' || e.key === 'Enter' || e.key === 'ArrowRight') {
                    e.preventDefault();
                    swapCards();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    swapCards(activeIndex - 1);
                }
            });
        })();
    });
</script>
@endsection
