@extends('layouts.app')

@section('title', 'Bảng điều khiển học tập | ' . config('app.name'))

@section('content')
<div class="space-y-8">
    <!-- Banner chào mừng & Thông tin học tập -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        Không gian học tập cá nhân
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600" title="Đơn vị tiến độ">
                        💡 Một từ có thể có nhiều nghĩa — mỗi nghĩa là một đơn vị tiến độ riêng
                    </span>
                </div>
                <h1 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Xin chào, {{ $user->name }}! 👋
                </h1>
                <p class="mt-2 text-sm text-slate-600">
                    Sẵn sàng cho các buổi học tập trung và củng cố trí nhớ dài hạn hôm nay.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('decks.create') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/20 cursor-pointer">
                    <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tạo bộ từ mới
                </a>
            </div>
        </div>

        <!-- Phiên học chưa kết thúc (nếu có) -->
        @if ($stats['active_session'])
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start space-x-3">
                        <div class="rounded-xl bg-amber-100 p-2 text-amber-700 shrink-0 mt-0.5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="inline-flex items-center rounded-md bg-amber-200/60 px-2 py-0.5 text-xs font-semibold text-amber-900">
                                Phiên chưa kết thúc
                            </span>
                            <h3 class="mt-1 text-base font-bold text-slate-900">
                                {{ $stats['active_session']->deck->name }}
                            </h3>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Chế độ: <span class="font-medium text-slate-800">{{ $stats['active_session']->mode->label() }}</span> &bull;
                                Giai đoạn: <span class="font-medium text-slate-800">{{ $stats['active_session']->stage->label() }}</span> &bull;
                                Đã xử lý: <span class="font-semibold text-amber-800">{{ $stats['active_session']->completedItemsCount() }}/{{ $stats['active_session']->items->count() }} nghĩa</span> &bull;
                                Tương tác gần nhất: {{ $stats['active_session']->last_interacted_at ? $stats['active_session']->last_interacted_at->diffForHumans() : 'Vừa xong' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <a href="{{ route('study.session.show', $stats['active_session']) }}"
                           class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-amber-700 transition">
                            Tiếp tục phiên học &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- 4 Nút / Thẻ chế độ học tập cốt lõi -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Chế độ 1: Học từ mới -->
        <div class="flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">Học mới</span>
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">Học từ mới</h2>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Làm quen từng nét nghĩa chưa học, hiểu rõ ngữ cảnh ví dụ và khởi tạo lịch ôn SRS.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="{{ route('study.new.select') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-indigo-700 transition">
                    Học từ mới &rarr;
                </a>
            </div>
        </div>

        <!-- Chế độ 2: Ôn tập (với số nghĩa đến hạn thực tế) -->
        <div class="flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    @if ($stats['due_count'] > 0)
                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-700">
                            {{ $stats['due_count'] }} nghĩa đến hạn
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                            0 đến hạn
                        </span>
                    @endif
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">Ôn đến hạn</h2>
                <div class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    @if ($stats['due_count'] > 0)
                        <p class="text-rose-700 font-medium">
                            Bạn có <span class="font-bold">{{ $stats['due_count'] }}</span> nghĩa cần ôn hôm nay theo thuật toán SRS.
                        </p>
                    @elseif (!$stats['has_studied'])
                        <p class="text-slate-500">
                            Chưa có lịch ôn tập. Hãy học từ mới trước để hệ thống bắt đầu theo dõi và lên lịch ôn tập cho bạn.
                        </p>
                    @elseif ($stats['next_due_at'])
                        <p class="text-slate-600">
                            Không có nghĩa nào đến hạn lúc này. Lịch ôn tập gần nhất tiếp theo là vào <span class="font-semibold text-slate-900">{{ $stats['next_due_at']->format('H:i, d/m/Y') }}</span> ({{ $stats['next_due_at']->diffForHumans() }}).
                        </p>
                    @else
                        <p class="text-slate-500">
                            Tuyệt vời! Hiện không còn nghĩa nào đến hạn ôn tập.
                        </p>
                    @endif
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100">
                @if ($stats['due_count'] > 0)
                    <a href="{{ route('study.review.index') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-amber-700 transition">
                        Ôn tập ngay ({{ $stats['due_count'] }}) &rarr;
                    </a>
                @else
                    <a href="{{ route('study.review.index') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Xem lịch ôn &rarr;
                    </a>
                @endif
            </div>
        </div>

        <!-- Chế độ 3: Luyện theo dạng bài & Từ khó -->
        <div class="flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700">Tự do</span>
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">Luyện tập</h2>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Luyện tự do theo 8 dạng bài hoặc củng cố từ khó. Mặc định không thay đổi lịch ôn SRS.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="{{ route('study.practice.index') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-purple-700 transition">
                    Luyện tập &rarr;
                </a>
            </div>
        </div>

        <!-- Chế độ 4: Kiểm tra -->
        <div class="flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Đánh giá</span>
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">Kiểm tra</h2>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Đánh giá kiến thức với tính giờ và bảng câu hỏi, không lộ đáp án trong bài kiểm tra.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="{{ route('study.quiz.index') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-emerald-700 transition">
                    Kiểm tra &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Danh sách bộ từ gần đây & Trạng thái rỗng -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold tracking-tight text-slate-900">
                Bộ từ vựng của bạn
            </h2>
            @if ($stats['total_decks'] > 0)
                <a href="{{ route('decks.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                    Xem tất cả ({{ $stats['total_decks'] }}) &rarr;
                </a>
            @endif
        </div>

        @if ($stats['total_decks'] === 0)
            <!-- Trạng thái: Chưa có bộ từ -->
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-900">Bạn chưa tạo bộ từ vựng nào</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                    Bắt đầu hành trình học tập bằng cách tạo bộ từ của riêng bạn hoặc khám phá các bộ từ công khai do cộng đồng chia sẻ.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('decks.create') }}"
                       class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        + Tạo bộ từ đầu tiên
                    </a>
                    <a href="{{ route('explore') }}"
                       class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                        🔍 Khám phá bộ từ cộng đồng
                    </a>
                </div>
            </div>
        @elseif ($stats['decks_with_words_count'] === 0)
            <!-- Trạng thái: Có bộ nhưng chưa có từ -->
            <div class="rounded-3xl border border-dashed border-amber-300 bg-amber-50/40 p-8 text-center shadow-xs">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="mt-3 text-base font-bold text-slate-900">Các bộ từ của bạn hiện chưa có từ vựng</h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">
                    Bạn cần thêm từ vựng vào bộ từ để có thể bắt đầu các phiên học từ mới, ôn tập hoặc làm bài kiểm tra.
                </p>
                <div class="mt-5 flex justify-center">
                    <a href="{{ route('decks.index') }}"
                       class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-amber-700 transition">
                        Quản lý bộ từ & thêm từ mới &rarr;
                    </a>
                </div>
            </div>
        @else
            <!-- Danh sách bộ từ -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($stats['recent_decks'] as $deck)
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:border-indigo-200 hover:shadow-sm">
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span class="font-medium text-slate-700">
                                    {{ $deck->learningLanguage->name }}
                                    @if ($deck->learning_language_id !== $deck->meaning_language_id)
                                        &rarr; {{ $deck->meaningLanguage->name }}
                                    @else
                                        (Đơn ngữ)
                                    @endif
                                </span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                                    {{ $deck->entries_count }} mục từ
                                </span>
                            </div>
                            <h3 class="mt-2 text-base font-bold text-slate-900 line-clamp-1">
                                <a href="{{ route('decks.show', $deck) }}" class="hover:text-indigo-600 transition">
                                    {{ $deck->name }}
                                </a>
                            </h3>
                            @if ($deck->description)
                                <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ $deck->description }}</p>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between pt-3 border-t border-slate-100">
                            <a href="{{ route('decks.show', $deck) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                                Chi tiết
                            </a>
                            <a href="{{ route('study.new.prepare', $deck) }}"
                               class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                                Học từ mới &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
