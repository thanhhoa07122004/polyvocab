@extends('layouts.app')

@section('title', 'Chuẩn bị ôn tập từ vựng | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-8">
    <div>
        <div class="flex items-center space-x-2 text-sm text-slate-500">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-800 transition">Bảng điều khiển</a>
            <span>/</span>
            <span class="font-semibold text-slate-800">Ôn tập từ vựng</span>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Ôn tập từ vựng
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Ôn lại các từ đã học đúng lúc để ghi nhớ lâu hơn.
                </p>
            </div>
            <div class="inline-flex items-center self-start rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 border border-amber-200">
                Lịch ôn ngắt quãng
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Thẻ phiên dở dang nếu có -->
    @if ($activeReviewSession)
        <div class="rounded-3xl border border-amber-300 bg-amber-50/80 p-6 shadow-xs">
            <div class="flex items-start space-x-4">
                <div class="rounded-2xl bg-amber-200/70 p-3 text-amber-800 shrink-0">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <span class="inline-flex items-center rounded-md bg-amber-200 px-2 py-0.5 text-xs font-bold text-amber-900">
                        BẠN ĐANG ÔN DỞ
                    </span>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">
                        {{ $activeReviewSession->deck->name }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-600">
                        Đã ôn: <span class="font-bold text-amber-900">{{ $activeReviewSession->completedItemsCount() }}/{{ $activeReviewSession->items->count() }} nghĩa</span> &bull;
                        Học gần nhất: {{ $activeReviewSession->last_interacted_at ? $activeReviewSession->last_interacted_at->diffForHumans() : 'Vừa xong' }}
                    </p>
                    <div class="mt-4 flex items-center gap-3">
                        <a href="{{ route('study.session.show', $activeReviewSession) }}"
                           class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-amber-700 transition">
                            Tiếp tục ôn &rarr;
                        </a>
                        <form method="POST" action="{{ route('study.session.abandon', $activeReviewSession) }}"
                              onsubmit="return confirm('Kết quả đã lưu vẫn được giữ. Phần chưa học sẽ được trả về danh sách để bạn chọn lại.');">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-rose-600 transition cursor-pointer">
                                Kết thúc lần ôn này
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Thống kê nghĩa đến hạn & quá hạn -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-b border-slate-100 pb-6">
            <div class="rounded-2xl bg-amber-50/60 p-4 border border-amber-100">
                <div class="text-xs font-medium text-amber-800">Tổng nghĩa đến hạn</div>
                <div class="mt-1 text-2xl font-black text-amber-700">{{ $totalDue }}</div>
                <p class="mt-1 text-2xs text-amber-800/80">Cần củng cố hôm nay</p>
            </div>
            <div class="rounded-2xl bg-rose-50/60 p-4 border border-rose-100">
                <div class="text-xs font-medium text-rose-800">Trong đó đã quá hạn</div>
                <div class="mt-1 text-2xl font-black text-rose-700">{{ $overdueCount }}</div>
                <p class="mt-1 text-2xs text-rose-800/80">Quá hạn > 24 giờ</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                <div class="text-xs font-medium text-slate-500">Lịch ôn tiếp theo</div>
                <div class="mt-1 text-sm font-bold text-slate-800">
                    {{ $nextDueAt ? $nextDueAt->format('H:i, d/m/Y') : 'Không có' }}
                </div>
                <p class="mt-1 text-2xs text-slate-500">
                    {{ $nextDueAt ? $nextDueAt->diffForHumans() : 'Hiện chưa có từ nào đến hạn' }}
                </p>
            </div>
        </div>

        <!-- Form thiết lập ôn tập -->
        <form method="POST" action="{{ route('study.review.start') }}" class="mt-6 space-y-6">
            @csrf

            <!-- 1. Bộ lọc bộ từ & ngôn ngữ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="deckSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Phạm vi bộ từ:
                    </label>
                    <select id="deckSelect" name="deck_ids[]"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-2xs focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                        <option value="">Tất cả các bộ từ có từ đến hạn</option>
                        @foreach ($decks as $d)
                            <option value="{{ $d->id }}" {{ $selectedDeckId === $d->id ? 'selected' : '' }}>
                                {{ $d->name }} ({{ $d->due_count }} đến hạn)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="langSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Lọc theo ngôn ngữ học:
                    </label>
                    <select id="langSelect" name="language_id"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-2xs focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                        <option value="">Tất cả ngôn ngữ</option>
                        @foreach ($languages as $lang)
                            <option value="{{ $lang->id }}" {{ $selectedLanguageId === $lang->id ? 'selected' : '' }}>
                                {{ $lang->name }} ({{ $lang->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 2. Số lượng từ muốn ôn -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <label for="countInput" class="block text-sm font-bold text-slate-900">
                    Số từ muốn ôn:
                </label>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($presets as $p)
                        <button type="button"
                                onclick="document.getElementById('countInput').value = {{ $p }}"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-amber-50 hover:text-amber-800 hover:border-amber-300 transition cursor-pointer">
                            {{ $p }} từ
                        </button>
                    @endforeach
                </div>
                <div class="flex items-center space-x-3">
                    <input type="number"
                           id="countInput"
                           name="count"
                           value="{{ old('count', $defaultCount) }}"
                           min="1"
                           max="50"
                           required
                           class="w-28 rounded-xl border border-slate-300 px-3 py-2 text-center text-sm font-bold text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    <span class="text-xs text-slate-500">từ (Tối đa 50 từ mỗi lần)</span>
                </div>
            </div>

            <!-- 3. Tùy chọn Luyện thêm ngoài lịch -->
            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200/70 space-y-2">
                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_extra_practice"
                           value="1"
                           {{ $totalDue === 0 ? 'checked' : '' }}
                           class="mt-0.5 h-4 w-4 rounded-md border-slate-300 text-amber-600 focus:ring-amber-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-800">Luyện thêm tự do</span>
                        <p class="text-slate-500 mt-0.5">
                            Luyện lại các nghĩa đã học khi chưa đến hạn. Lần luyện này không làm thay đổi lịch ôn tập.
                        </p>
                    </div>
                </label>
            </div>

            <!-- Nút Bắt đầu ôn -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 transition">
                    &larr; Về bảng điều khiển
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700 transition focus:outline-none focus:ring-2 focus:ring-amber-600/20 cursor-pointer">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Bắt đầu ôn tập &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
