@extends('layouts.app')

@section('title', __('study.learn_new_title') . ': ' . $deck->name . ' | ' . config('app.name'))

@section('content')
@php
    // Lọc các mốc preset thực sự nhỏ hơn availableCount (mốc bằng availableCount đã là nút "Tất cả")
    $applicablePresets = array_values(array_filter($presets, fn ($p) => $p < $availableCount));

    // Giá trị khởi tạo mặc định cho số lượng
    $initialCount = old('count', ($availableCount > 0 ? min($defaultCount, $availableCount) : 0));
    
    // Kiểm tra xem initialCount có trùng với preset hay "Tất cả"
    $isPresetActive = in_array($initialCount, $applicablePresets, true);
    $isAllActive = ($initialCount === $availableCount && $availableCount > 0);
    $isCustomActive = (! $isPresetActive && ! $isAllActive && $availableCount > 0);
@endphp

<div class="mx-auto max-w-4xl space-y-6 sm:space-y-8">
    {{-- 1. ĐẦU TRANG & KHỐI THÔNG TIN GỌN --}}
    <div>
        {{-- Breadcrumb nhỏ gọn --}}
        <nav aria-label="Breadcrumb" class="flex items-center space-x-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-900 transition">Bảng điều khiển</a>
            <span>/</span>
            <a href="{{ route('decks.show', $deck) }}" class="hover:text-slate-900 transition truncate max-w-[200px] sm:max-w-xs">{{ $deck->name }}</a>
            <span>/</span>
            <span class="font-semibold text-slate-800">{{ __('study.learn_new_title') }}</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    {{ __('study.learn_new_title') }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ __('study.learn_new_desc', ['name' => $deck->name]) }}
                </p>
            </div>

            {{-- Khối thông tin bộ từ thu gọn --}}
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs">
                <div class="inline-flex items-center space-x-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 shadow-2xs">
                    <span class="text-slate-400 font-medium">{{ __('study.language') }}:</span>
                    <span class="font-semibold text-slate-800">{{ $deck->learningLanguage->name }}</span>
                    <span class="text-slate-300">&rarr;</span>
                    <span class="font-semibold text-slate-800">{{ $deck->meaningLanguage->name }}</span>
                </div>

                <div class="inline-flex items-center space-x-1.5 rounded-xl border border-indigo-100 bg-indigo-50/70 px-3 py-1.5 text-indigo-800 shadow-2xs">
                    @if (($unstudiedCount ?? 1) > 0)
                        <span class="font-bold">{{ __('study.unstudied_content', ['count' => $availableCount]) }}</span>
                    @else
                        <span class="font-bold">Có {{ $availableCount }} từ vựng trong bộ</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Thông báo flash --}}
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

    {{-- Cảnh báo nếu đang có phiên dở dang --}}
    @if ($activeSession)
        <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start space-x-3">
                    <div class="rounded-xl bg-amber-200/80 p-2 text-amber-800 shrink-0 mt-0.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-amber-800 uppercase tracking-wide">
                            {{ __('study.session_in_progress') }}
                        </div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 mt-0.5">
                            @if ($activeSession->completedItemsCount() > 0)
                                {{ __('study.active_session_has_progress') }}
                            @else
                                {{ __('study.active_session_no_progress') }}
                            @endif
                        </h2>
                        <div class="text-xs text-slate-600 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                            <span>Chế độ: <strong class="text-slate-800">{{ $activeSession->mode->label() }}</strong></span>
                            <span>&bull;</span>
                            <span>Đã học: <strong class="text-slate-800">{{ $activeSession->completedItemsCount() }}/{{ $activeSession->items->count() }} từ</strong></span>
                            <span>&bull;</span>
                            <span>Học gần nhất: {{ $activeSession->last_interacted_at ? $activeSession->last_interacted_at->diffForHumans() : 'Vừa xong' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                    <a href="{{ route('study.session.show', $activeSession) }}"
                       class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-2xs hover:bg-amber-700 transition">
                        {{ __('study.resume_session') }} &rarr;
                    </a>

                    <form method="POST" action="{{ route('study.session.abandon', $activeSession) }}"
                          onsubmit="return confirm('{{ __('study.abandon_confirm') }}');">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 transition cursor-pointer">
                            {{ __('study.abandon_session') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- NỘI DUNG CHÍNH --}}
    @if ($availableCount === 0)
        {{-- Trạng thái rỗng: Đã học hết từ mới --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center max-w-2xl mx-auto shadow-xs">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-3">
                ✓
            </div>
            <h2 class="text-lg font-bold text-slate-900">
                Bạn đã làm quen hết các từ trong bộ từ này
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Hãy thêm từ vựng mới hoặc chuyển sang Luyện tập / Ôn tập để củng cố các từ đã học.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if ($deck->user_id === auth()->id())
                    <a href="{{ route('decks.entries.create', $deck) }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                        + Thêm từ vựng mới
                    </a>
                @endif
                <a href="{{ route('study.practice.deck', $deck) }}" class="inline-flex items-center rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-purple-500 transition">
                    Luyện tập bộ này
                </a>
                <a href="{{ route('study.review.index', ['deck_id' => $deck->id]) }}" class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500 transition">
                    Đến trang Ôn tập
                </a>
                <a href="{{ route('decks.show', $deck) }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    {{ __('study.back_to_deck') }}
                </a>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('study.new.start', $deck) }}" id="learnNewForm">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {{-- CỘT TRÁI (7 cột): Chọn số lượng --}}
                <div class="lg:col-span-7 space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                {{ __('study.how_many_today') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ __('study.remaining_items', ['count' => $availableCount]) }}
                            </p>
                        </div>

                        {{-- Nhóm nút chọn nhanh số lượng --}}
                        <div class="flex flex-wrap items-center gap-2.5">
                            @foreach ($applicablePresets as $preset)
                                <button type="button"
                                        class="count-preset-btn inline-flex items-center space-x-1.5 rounded-xl border px-3.5 py-2 text-xs font-semibold transition cursor-pointer {{ $initialCount === $preset ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 hover:bg-white' }}"
                                        data-count="{{ $preset }}"
                                        onclick="window.selectBatchCount({{ $preset }}, this)">
                                    <span class="count-check {{ $initialCount === $preset ? '' : 'hidden' }}">✓</span>
                                    <span>{{ __('study.item_unit_short', ['count' => $preset]) }}</span>
                                </button>
                            @endforeach

                            {{-- Nút Tất cả --}}
                            <button type="button"
                                    class="count-preset-btn inline-flex items-center space-x-1.5 rounded-xl border px-3.5 py-2 text-xs font-semibold transition cursor-pointer {{ $isAllActive ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 hover:bg-white' }}"
                                    data-count="{{ $availableCount }}"
                                    onclick="window.selectBatchCount({{ $availableCount }}, this)">
                                <span class="count-check {{ $isAllActive ? '' : 'hidden' }}">✓</span>
                                <span>{{ __('study.all_items', ['count' => $availableCount]) }}</span>
                            </button>

                            {{-- Nút Tùy chỉnh --}}
                            <button type="button"
                                    id="btn-custom-count"
                                    class="count-preset-btn inline-flex items-center space-x-1.5 rounded-xl border px-3.5 py-2 text-xs font-semibold transition cursor-pointer {{ $isCustomActive ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 hover:bg-white' }}"
                                    onclick="window.activateCustomCount(this)">
                                <span class="count-check {{ $isCustomActive ? '' : 'hidden' }}">✓</span>
                                <span>{{ __('study.custom') }}</span>
                            </button>
                        </div>

                        {{-- Ô nhập Tùy chỉnh (chỉ hiện khi bấm Tùy chỉnh) --}}
                        <div id="custom-count-container" class="{{ $isCustomActive ? '' : 'hidden' }} pt-2 flex items-center space-x-3">
                            <label for="countInput" class="text-xs text-slate-600 font-medium">Nhập số từ:</label>
                            <div class="relative w-28">
                                <input type="number"
                                       id="countInput"
                                       name="count"
                                       value="{{ $initialCount }}"
                                       min="{{ $minBatch }}"
                                       max="{{ min($maxBatch, $availableCount) }}"
                                       oninput="window.handleCustomInput(this)"
                                       required
                                       class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-center text-sm font-bold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <span class="text-xs text-slate-500">
                                (từ {{ $minBatch }} đến {{ min($maxBatch, $availableCount) }} từ)
                            </span>
                        </div>

                        @error('count')
                            <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror

                        {{-- Dòng giải thích --}}
                        <div class="pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-start space-x-2">
                            <span class="text-indigo-600 text-base leading-none">💡</span>
                            <span>{{ __('study.familiarize_then_practice') }}</span>
                        </div>
                    </div>
                </div>

                {{-- CỘT PHẢI (5 cột): Tóm tắt & Bắt đầu --}}
                <div class="lg:col-span-5 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-slate-400">
                            {{ __('study.session_summary') }}
                        </h2>

                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Bộ từ:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[200px]">{{ $deck->name }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">{{ __('study.language') }}:</span>
                                <span class="font-semibold text-slate-800">
                                    {{ $deck->learningLanguage->name }} &rarr; {{ $deck->meaningLanguage->name }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between border-t border-slate-100 pt-2.5">
                                <span class="text-slate-600 font-medium">Số từ làm quen:</span>
                                <span class="font-extrabold text-indigo-700 text-sm" id="summary-count-display">
                                    {{ __('study.item_unit_short', ['count' => $initialCount]) }}
                                </span>
                            </div>

                            <div class="border-t border-slate-100 pt-2.5 text-[11px] text-slate-500 text-center font-medium bg-slate-50 rounded-xl py-2">
                                {{ __('study.flow_steps') }}
                            </div>
                        </div>

                        {{-- Nút bắt đầu học --}}
                        <div class="pt-2 space-y-2">
                            <button type="submit"
                                    id="startSubmitBtn"
                                    class="w-full flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30 cursor-pointer">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span id="startSubmitBtnText">
                                    {{ __('study.start_learning_with_count', ['count' => $initialCount]) }}
                                </span>
                            </button>

                            <p class="text-[11px] text-slate-400 text-center leading-tight">
                                {{ __('study.progress_saved_note') }}
                            </p>
                        </div>

                        <div class="border-t border-slate-100 pt-2 text-center">
                            <a href="{{ route('decks.show', $deck) }}" class="text-xs font-medium text-slate-500 hover:text-slate-800 transition">
                                &larr; {{ __('study.back_to_deck') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

{{-- SCRIPT ĐIỀU KHIỂN TƯƠNG TÁC --}}
<script>
    const availableCount = {{ (int) $availableCount }};
    const minBatch = {{ (int) $minBatch }};
    const maxBatch = {{ (int) min($maxBatch, $availableCount) }};

    window.selectBatchCount = function(val, btnEl) {
        val = Math.max(minBatch, Math.min(val, availableCount));
        
        const input = document.getElementById('countInput');
        if (input) {
            input.value = val;
        }

        const customContainer = document.getElementById('custom-count-container');
        if (customContainer) {
            customContainer.classList.add('hidden');
        }

        document.querySelectorAll('.count-preset-btn').forEach(b => {
            b.classList.remove('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-700', 'ring-2', 'ring-indigo-500/20');
            b.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-700');
            const check = b.querySelector('.count-check');
            if (check) check.classList.add('hidden');
        });

        if (btnEl) {
            btnEl.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-700');
            btnEl.classList.add('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-700', 'ring-2', 'ring-indigo-500/20');
            const check = btnEl.querySelector('.count-check');
            if (check) check.classList.remove('hidden');
        }

        updateSummaryUI(val);
    };

    window.activateCustomCount = function(btnEl) {
        const customContainer = document.getElementById('custom-count-container');
        if (customContainer) {
            customContainer.classList.remove('hidden');
        }

        document.querySelectorAll('.count-preset-btn').forEach(b => {
            b.classList.remove('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-700', 'ring-2', 'ring-indigo-500/20');
            b.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-700');
            const check = b.querySelector('.count-check');
            if (check) check.classList.add('hidden');
        });

        if (btnEl) {
            btnEl.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-700');
            btnEl.classList.add('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-700', 'ring-2', 'ring-indigo-500/20');
            const check = btnEl.querySelector('.count-check');
            if (check) check.classList.remove('hidden');
        }

        const input = document.getElementById('countInput');
        if (input) {
            input.focus();
            input.select();
            updateSummaryUI(parseInt(input.value) || minBatch);
        }
    };

    window.handleCustomInput = function(inputEl) {
        let val = parseInt(inputEl.value);
        if (isNaN(val)) val = minBatch;
        if (val > maxBatch) val = maxBatch;
        if (val < minBatch && inputEl.value !== '') val = minBatch;
        updateSummaryUI(val);
    };

    function updateSummaryUI(count) {
        const summaryCount = document.getElementById('summary-count-display');
        if (summaryCount) {
            summaryCount.textContent = count + ' từ';
        }

        const startSubmitBtnText = document.getElementById('startSubmitBtnText');
        if (startSubmitBtnText) {
            startSubmitBtnText.textContent = 'Bắt đầu làm quen · ' + count + ' từ';
        }
    }
</script>
@endsection
