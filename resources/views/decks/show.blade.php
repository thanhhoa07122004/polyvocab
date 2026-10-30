@extends('layouts.app')

@section('title', $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="space-y-8">
    <!-- Header thông tin bộ từ -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div class="space-y-3 flex-1">
                <!-- Breadcrumbs & Badges -->
                <div class="flex flex-wrap items-center gap-2">
                    @auth
                        @if (auth()->id() === $deck->user_id)
                            <a href="{{ route('decks.index') }}" class="text-xs font-medium text-slate-500 hover:text-indigo-600 transition">
                                &larr; Bộ từ của tôi
                            </a>
                        @else
                            <a href="{{ route('explore') }}" class="text-xs font-medium text-slate-500 hover:text-indigo-600 transition">
                                &larr; Khám phá bộ từ
                            </a>
                        @endif
                    @else
                        <a href="{{ route('explore') }}" class="text-xs font-medium text-slate-500 hover:text-indigo-600 transition">
                            &larr; Khám phá bộ từ
                        </a>
                    @endauth

                    <span class="text-xs text-slate-300">&bull;</span>

                    <!-- Visibility Badge -->
                    @if ($deck->isPublic())
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                            <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Công khai
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 border border-slate-200">
                            <svg class="w-3 h-3 mr-1 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Riêng tư
                        </span>
                    @endif

                    <span class="text-xs text-slate-300">&bull;</span>
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 border border-indigo-100">
                        {{ number_format($totalEntriesCount) }} từ
                    </span>

                    <span class="text-xs text-slate-300">&bull;</span>
                    <span class="inline-flex items-center space-x-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 border border-slate-200" title="Cặp ngôn ngữ học">
                        <span lang="{{ $deck->learningLanguage->code }}" dir="{{ $deck->learningLanguage->direction }}">{{ $deck->learningLanguage->name }}</span>
                        <span class="text-slate-400 font-normal">&rarr;</span>
                        <span lang="{{ $deck->meaningLanguage->code }}" dir="{{ $deck->meaningLanguage->direction }}">{{ $deck->meaningLanguage->name }}</span>
                    </span>
                </div>

                <!-- Tên bộ từ áp dụng hướng chữ của ngôn ngữ học -->
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900"
                    lang="{{ $deck->learningLanguage->code }}"
                    dir="{{ $deck->learningLanguage->direction }}">
                    {{ $deck->name }}
                </h1>

                <!-- Mô tả bộ từ -->
                @if ($deck->description)
                    <p class="text-sm sm:text-base text-slate-600 leading-relaxed whitespace-pre-line">
                        {{ $deck->description }}
                    </p>
                @endif

                <!-- Thông tin tác giả, ngày tạo & ngôn ngữ -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500 pt-1">
                    <div class="flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Tạo bởi: <strong class="font-medium text-slate-700">{{ $deck->user->name }}</strong></span>
                    </div>

                    <span>&bull;</span>

                    <div class="flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Ngày tạo: {{ $deck->created_at ? $deck->created_at->format('d/m/Y') : '' }}</span>
                    </div>

                    <span>&bull;</span>

                    <div class="flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                        <span>
                            <strong class="font-medium text-slate-700">{{ $deck->learningLanguage->name }}</strong>
                            @if ($deck->learningLanguage->native_name)
                                <span class="text-slate-500 font-normal" lang="{{ $deck->learningLanguage->code }}" dir="{{ $deck->learningLanguage->direction }}">({{ $deck->learningLanguage->native_name }})</span>
                            @endif
                            <span class="text-slate-400 font-mono text-[11px]">[{{ $deck->learningLanguage->code }}]</span>
                            <span class="text-slate-400">&rarr;</span>
                            <strong class="font-medium text-slate-700">{{ $deck->meaningLanguage->name }}</strong>
                            @if ($deck->meaningLanguage->native_name)
                                <span class="text-slate-500 font-normal" lang="{{ $deck->meaningLanguage->code }}" dir="{{ $deck->meaningLanguage->direction }}">({{ $deck->meaningLanguage->native_name }})</span>
                            @endif
                            <span class="text-slate-400 font-mono text-[11px]">[{{ $deck->meaningLanguage->code }}]</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Thao tác của người học & chủ sở hữu -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @can('update', $deck)
                    <a href="{{ route('decks.entries.create', $deck) }}"
                       class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Thêm từ vựng
                    </a>

                    <a href="{{ route('decks.edit', $deck) }}"
                       class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Sửa bộ từ
                    </a>

                    <form method="POST" action="{{ route('decks.destroy', $deck) }}"
                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn bộ từ « {{ addslashes($deck->name) }} »?');"
                          class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-xl border border-rose-200 bg-white px-3.5 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            Xóa
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <!-- Trung tâm học tập bộ từ (Study Center) -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    Tiến độ & Chế độ học tập
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Một từ có thể có nhiều nghĩa — mỗi nghĩa là một phần cần ghi nhớ.
                </p>
            </div>
            <span class="inline-flex items-center text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                {{ number_format($totalEntriesCount) }} từ &bull; {{ number_format($totalMeaningsCount) }} nghĩa
            </span>
        </div>

        @auth
            <!-- Ô thống kê tiến độ cá nhân -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 deck-stat-card deck-stat-total">
                    <span class="text-xs font-medium text-slate-500 block">Tổng số từ vựng</span>
                    <span class="text-xl font-bold text-slate-900 mt-1 block">{{ number_format($totalMeaningsCount) }}</span>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">trong {{ number_format($totalEntriesCount) }} từ</span>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 deck-stat-card deck-stat-unstudied">
                    <span class="text-xs font-semibold text-emerald-700 block">Từ chưa học</span>
                    <span class="text-xl font-bold text-emerald-900 mt-1 block">{{ number_format($studyStats['unstudied_count']) }}</span>
                    <span class="text-[11px] text-emerald-600 mt-0.5 block">Sẵn sàng để học mới</span>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4 deck-stat-card deck-stat-due">
                    <span class="text-xs font-semibold text-amber-700 block">Từ đến hạn ôn</span>
                    <div class="flex items-baseline space-x-1.5 mt-1">
                        <span class="text-xl font-bold text-amber-900">{{ number_format($studyStats['due_count']) }}</span>
                        @if ($studyStats['overdue_count'] > 0)
                            <span class="text-xs font-semibold text-rose-600">({{ $studyStats['overdue_count'] }} quá hạn)</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-amber-600 mt-0.5 block">Cần ôn theo lịch</span>
                </div>
                <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-4 deck-stat-card deck-stat-difficult">
                    <span class="text-xs font-semibold text-rose-700 block">Từ cần nhớ kỹ</span>
                    <span class="text-xl font-bold text-rose-900 mt-1 block">{{ number_format($studyStats['difficult_count']) }}</span>
                    <span class="text-[11px] text-rose-600 mt-0.5 block">Cần củng cố thêm</span>
                </div>
            </div>

            <!-- Thẻ phiên dở dang nếu có -->
            @if ($studyStats['active_session'])
                @php $activeSess = $studyStats['active_session']; @endphp
                <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start space-x-3">
                        <div class="p-2.5 rounded-xl bg-amber-200 text-amber-900 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-slate-900">BẠN ĐANG HỌC DỞ: {{ $activeSess->mode->label() }}</span>
                                <span class="text-[10px] bg-amber-200 text-amber-800 px-2 py-0.5 rounded font-semibold">{{ $activeSess->stage->label() }}</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-1">
                                Tiến độ: <strong>{{ $activeSess->completedItemsCount() }}/{{ $activeSess->items->count() }} từ</strong> &bull;
                                Cập nhật: {{ $activeSess->last_interacted_at ? $activeSess->last_interacted_at->diffForHumans() : 'Vừa xong' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('study.session.show', $activeSess) }}"
                           class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-amber-500 transition">
                            Tiếp tục học &rarr;
                        </a>
                        <form method="POST" action="{{ route('study.session.abandon', $activeSess) }}"
                              onsubmit="return confirm('Kết quả đã lưu vẫn được giữ. Phần chưa học sẽ được trả về danh sách để bạn chọn lại.');">
                            @csrf
                            <button type="submit" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                                Kết thúc lần học này
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- 4 nút chế độ học tập chính -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 pt-2">
                <!-- 1. Học từ mới -->
                @if ($studyStats['unstudied_count'] > 0)
                    <a href="{{ route('study.new.prepare', $deck) }}"
                       class="group rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 hover:bg-emerald-50 hover:border-emerald-300 transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="p-2 rounded-xl bg-emerald-600 text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </span>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full">{{ $studyStats['unstudied_count'] }} mới</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 mt-3 group-hover:text-emerald-700 transition">Học từ mới</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Làm quen và ghi nhớ các từ mới trong bộ từ này.</p>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600 mt-4 block">Bắt đầu học &rarr;</span>
                    </a>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-75 flex flex-col justify-between cursor-not-allowed deck-mode-card-disabled"
                         title="Đã học hết tất cả các từ trong bộ từ này!">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="p-2 rounded-xl bg-slate-300 text-slate-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-[11px] font-semibold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full">Đã học hết</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-700 mt-3">Học từ mới</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Bạn đã làm quen tất cả các từ trong bộ này.</p>
                        </div>
                        <span class="text-xs text-slate-400 mt-4 block italic">Chưa có nghĩa mới</span>
                    </div>
                @endif

                <!-- 2. Ôn đến hạn -->
                @if ($studyStats['due_count'] > 0)
                    <a href="{{ route('study.review.index', ['deck_id' => $deck->id]) }}"
                       class="group rounded-2xl border border-amber-200 bg-amber-50/40 p-4 hover:bg-amber-50 hover:border-amber-300 transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="p-2 rounded-xl bg-amber-600 text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <span class="text-xs font-bold text-amber-700 bg-amber-100/70 px-2 py-0.5 rounded-full">{{ $studyStats['due_count'] }} đến hạn</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 mt-3 group-hover:text-amber-700 transition">Ôn đến hạn</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Ôn lại các từ cần củng cố theo chu kỳ ngắt quãng.</p>
                        </div>
                        <span class="text-xs font-semibold text-amber-600 mt-4 block">Bắt đầu ôn &rarr;</span>
                    </a>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-75 flex flex-col justify-between cursor-not-allowed deck-mode-card-disabled"
                         title="Chưa có từ nào đến hạn ôn tập hôm nay.">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="p-2 rounded-xl bg-slate-300 text-slate-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <span class="text-[11px] font-semibold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full">0 đến hạn</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-700 mt-3">Ôn đến hạn</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Chưa có từ nào đến hạn ôn tập hôm nay.</p>
                        </div>
                        <span class="text-xs text-slate-400 mt-4 block italic">Chưa đến hạn</span>
                    </div>
                @endif

                <!-- 3. Luyện theo dạng bài -->
                @if ($totalMeaningsCount > 0)
                    <a href="{{ route('study.practice.deck', $deck) }}"
                       class="group rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4 hover:bg-indigo-50 hover:border-indigo-300 transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="p-2 rounded-xl bg-indigo-600 text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </span>
                                <span class="text-xs font-bold text-indigo-700 bg-indigo-100/70 px-2 py-0.5 rounded-full">Tự do</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 mt-3 group-hover:text-indigo-700 transition">Luyện theo dạng bài</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Luyện tập tự do không thay đổi lịch ôn SRS.</p>
                        </div>
                        <span class="text-xs font-semibold text-indigo-600 mt-4 block">Tùy chọn luyện &rarr;</span>
                    </a>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-75 flex flex-col justify-between cursor-not-allowed deck-mode-card-disabled">
                        <div>
                            <h3 class="font-bold text-sm text-slate-700">Luyện theo dạng bài</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Bộ từ chưa có từ vựng nào.</p>
                        </div>
                        <span class="text-xs text-slate-400 mt-4 block italic">Chưa có dữ liệu</span>
                    </div>
                @endif

                <!-- 4. Luyện từ khó -->
                @if ($studyStats['difficult_count'] > 0)
                    <a href="{{ route('study.practice.deck', ['deck' => $deck->id, 'scope' => 'difficult']) }}"
                       class="group rounded-2xl border border-rose-200 bg-rose-50/40 p-4 hover:bg-rose-50 hover:border-rose-300 transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="p-2 rounded-xl bg-rose-600 text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </span>
                                <span class="text-xs font-bold text-rose-700 bg-rose-100/70 px-2 py-0.5 rounded-full">{{ $studyStats['difficult_count'] }} mục</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 mt-3 group-hover:text-rose-700 transition">Luyện từ khó</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Củng cố các từ bạn đã đánh dấu khó hoặc hay quên.</p>
                        </div>
                        <span class="text-xs font-semibold text-rose-600 mt-4 block">Luyện từ khó &rarr;</span>
                    </a>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-75 flex flex-col justify-between cursor-not-allowed deck-mode-card-disabled"
                         title="Chưa có từ nào được đánh dấu khó hoặc cần luyện thêm.">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="p-2 rounded-xl bg-slate-300 text-slate-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </span>
                                <span class="text-[11px] font-semibold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full">0 từ khó</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-700 mt-3">Luyện từ khó</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Chưa có từ nào được đánh dấu khó trong bộ từ này.</p>
                        </div>
                        <span class="text-xs text-slate-400 mt-4 block italic">Chưa có từ khó</span>
                    </div>
                @endif
            </div>
        @else
            <!-- Lời nhắc dành cho Khách chưa đăng nhập -->
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-6 text-center space-y-3">
                <p class="text-sm text-indigo-900 font-medium">
                    Hãy đăng nhập để theo dõi tiến độ từng nét nghĩa, ghi nhớ lâu dài và lưu lịch sử học tập.
                </p>
                <div class="flex items-center justify-center gap-3">
                    <a href="{{ route('login') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                        Đăng nhập ngay
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl border border-indigo-200 bg-white px-4 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 transition">
                        Tạo tài khoản miễn phí
                    </a>
                </div>
            </div>
        @endauth
    </div>

    <!-- Khu vực danh sách từ vựng -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-6">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    Danh sách từ vựng
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Tổng số: <strong class="font-semibold text-slate-700">{{ number_format($totalEntriesCount) }}</strong> từ vựng
                </p>
            </div>

            <!-- Tìm kiếm từ vựng trong bộ từ -->
            <form method="GET" action="{{ route('decks.show', $deck) }}" class="flex gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-72">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Tìm từ vựng hoặc ý nghĩa..."
                           class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-1.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <button type="submit"
                        class="rounded-xl bg-slate-900 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-slate-800 transition shrink-0">
                    Tìm
                </button>
                @if ($search !== '')
                    <a href="{{ route('decks.show', $deck) }}"
                       class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition shrink-0">
                        Xóa
                    </a>
                @endif
            </form>
        </div>

        @if ($entries->count() > 0)
            <div class="divide-y divide-slate-100">
                @foreach ($entries as $entry)
                    <div class="py-4 first:pt-0 last:pb-0 flex flex-col md:flex-row md:items-start md:justify-between gap-4 group">
                        <div class="space-y-2 flex-1">
                            <!-- Từ gốc & Phiên âm -->
                            <div class="flex flex-wrap items-baseline gap-2">
                                <a href="{{ route('decks.entries.show', [$deck, $entry]) }}"
                                   class="text-lg font-bold text-slate-900 hover:text-indigo-600 transition"
                                   lang="{{ $deck->learningLanguage->code }}"
                                   dir="{{ $deck->learningLanguage->direction }}">
                                    {{ $entry->term }}
                                </a>

                                @if ($entry->pronunciation)
                                    <span class="text-sm font-mono text-slate-500"
                                          lang="{{ $deck->learningLanguage->code }}"
                                          dir="{{ $deck->learningLanguage->direction }}">
                                        /{{ $entry->pronunciation }}/
                                    </span>
                                @endif

                                @php
                                    $exampleCount = $entry->meanings->sum(fn ($m) => $m->examples->count());
                                @endphp
                                @if ($exampleCount > 0)
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                        {{ $exampleCount }} ví dụ
                                    </span>
                                @endif
                            </div>

                            <!-- Danh sách nghĩa tóm tắt -->
                            <div class="space-y-1">
                                @foreach ($entry->meanings as $meaning)
                                    <div class="flex items-start text-sm text-slate-700">
                                        @if ($meaning->part_of_speech)
                                            <span class="inline-block rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-semibold text-indigo-700 mr-2 shrink-0">
                                                {{ $meaning->part_of_speech }}
                                            </span>
                                        @else
                                            <span class="inline-block text-slate-400 mr-2">&bull;</span>
                                        @endif
                                        @if ($meaning->meaning)
                                            <span class="line-clamp-2"
                                                  lang="{{ $deck->meaningLanguage->code }}"
                                                  dir="{{ $deck->meaningLanguage->direction }}">
                                                {{ $meaning->meaning }}
                                            </span>
                                        @else
                                            <span class="text-xs italic text-slate-400">
                                                (Chưa có nội dung ý nghĩa)
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if ($entry->note)
                                <p class="text-xs text-slate-400 line-clamp-1 italic">
                                    Ghi chú: {{ $entry->note }}
                                </p>
                            @endif
                        </div>

                        <!-- Thao tác trên từng từ -->
                        <div class="flex items-center space-x-2 shrink-0 pt-1">
                            <a href="{{ route('decks.entries.show', [$deck, $entry]) }}"
                               class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                                Chi tiết
                            </a>

                            @can('update', $deck)
                                <a href="{{ route('decks.entries.edit', [$deck, $entry]) }}"
                                   class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition">
                                    Sửa
                                </a>

                                <form method="POST" action="{{ route('decks.entries.destroy', [$deck, $entry]) }}"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa từ « {{ addslashes($entry->term) }} » khỏi bộ từ?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="rounded-lg border border-rose-100 bg-white px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                                        Xóa
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($entries->hasPages())
                <div class="mt-6 border-t border-slate-100 pt-6">
                    {{ $entries->links() }}
                </div>
            @endif
        @else
            <!-- Trạng thái rỗng hoặc không có kết quả tìm kiếm -->
            <div class="p-8 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>

                @if ($search !== '')
                    <h3 class="text-base font-bold text-slate-900">Không tìm thấy từ vựng</h3>
                    <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">
                        Không có từ vựng nào khớp với từ khóa « {{ $search }} » trong bộ từ này.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('decks.show', $deck) }}"
                           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                            Xóa tìm kiếm
                        </a>
                    </div>
                @else
                    <h3 class="text-base font-bold text-slate-900">Chưa có từ vựng</h3>
                    <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">
                        Bộ từ này hiện chưa có từ vựng nào.
                        @can('update', $deck)
                            Hãy thêm từ đầu tiên để bắt đầu lưu trữ và ôn tập!
                        @endcan
                    </p>

                    @can('update', $deck)
                        <div class="mt-4">
                            <a href="{{ route('decks.entries.create', $deck) }}"
                               class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Thêm từ đầu tiên
                            </a>
                        </div>
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
