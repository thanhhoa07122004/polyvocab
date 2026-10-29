@extends('layouts.app')

@section('title', 'Thêm từ mới | ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('decks.index') }}" class="hover:text-indigo-600">Bộ từ của tôi</a>
                <span>&rsaquo;</span>
                <a href="{{ route('decks.show', $deck) }}" class="hover:text-indigo-600 truncate max-w-[200px]">{{ $deck->name }}</a>
                <span>&rsaquo;</span>
                <span>Thêm từ mới</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Thêm từ vựng vào bộ từ
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Ngôn ngữ học: <strong class="text-indigo-600">{{ $deck->learningLanguage->name }}</strong> &bull;
                Giải nghĩa: <strong class="text-slate-700">{{ $deck->meaningLanguage->name }}</strong>
            </p>
        </div>
        <a href="{{ route('decks.show', $deck) }}"
           class="self-start sm:self-auto rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Về bộ từ
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
            <div class="font-semibold mb-1">Vui lòng kiểm tra lại thông tin:</div>
            <ul class="list-disc list-inside text-xs space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('decks.entries.store', $deck) }}" id="batch-entry-form" class="space-y-6">
        @csrf

        @php
            $oldEntries = old('entries', [
                ['term' => '', 'pronunciation' => '', 'part_of_speech' => '', 'meaning' => '', 'example_sentence' => '', 'example_translation' => '', 'note' => ''],
                ['term' => '', 'pronunciation' => '', 'part_of_speech' => '', 'meaning' => '', 'example_sentence' => '', 'example_translation' => '', 'note' => ''],
            ]);
            if (empty($oldEntries)) {
                $oldEntries = [
                    ['term' => '', 'pronunciation' => '', 'part_of_speech' => '', 'meaning' => '', 'example_sentence' => '', 'example_translation' => '', 'note' => ''],
                ];
            }
        @endphp

        <!-- Danh sách khung nhập từ mới -->
        <div id="word-cards-container" class="space-y-4">
            @foreach ($oldEntries as $idx => $entry)
                <div class="word-card rounded-3xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm hover:border-indigo-200 transition space-y-4 relative" data-index="{{ $idx }}">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs card-number font-mono">
                                {{ $loop->iteration }}
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                Khung từ vựng
                            </span>
                        </div>

                        <button type="button"
                                class="remove-card-btn text-xs text-rose-500 hover:text-rose-700 font-medium px-2 py-1 rounded hover:bg-rose-50 transition cursor-pointer">
                            &times; Xóa khung
                        </button>
                    </div>

                    <!-- Hàng 1: Từ vựng, Phiên âm, Từ loại -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <div class="sm:col-span-5">
                            <label class="block text-xs font-semibold text-slate-800 mb-1">
                                Từ hoặc cụm từ <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   name="entries[{{ $idx }}][term]"
                                   value="{{ $entry['term'] ?? '' }}"
                                   placeholder="Ví dụ: apple, study, 食べる..."
                                   maxlength="255"
                                   lang="{{ $deck->learningLanguage->code }}"
                                   dir="{{ $deck->learningLanguage->direction }}"
                                   class="term-input w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-base font-bold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Phiên âm <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                            </label>
                            <input type="text"
                                   name="entries[{{ $idx }}][pronunciation]"
                                   value="{{ $entry['pronunciation'] ?? '' }}"
                                   placeholder="Ví dụ: /ˈæpl/..."
                                   maxlength="255"
                                   lang="{{ $deck->learningLanguage->code }}"
                                   dir="{{ $deck->learningLanguage->direction }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-mono text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Từ loại <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                            </label>
                            <input type="text"
                                   name="entries[{{ $idx }}][part_of_speech]"
                                   value="{{ $entry['part_of_speech'] ?? '' }}"
                                   placeholder="n, v, adj, Danh từ..."
                                   maxlength="100"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>

                    <!-- Hàng 2: Ý nghĩa -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-800 mb-1">
                            Ý nghĩa <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                        </label>
                        <input type="text"
                               name="entries[{{ $idx }}][meaning]"
                               value="{{ $entry['meaning'] ?? '' }}"
                               placeholder="Giải thích nghĩa bằng {{ $deck->meaningLanguage->name }}..."
                               maxlength="2000"
                               lang="{{ $deck->meaningLanguage->code }}"
                               dir="{{ $deck->meaningLanguage->direction }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <!-- Hàng 3: Ví dụ & Bản dịch (gọn gàng trong 1 hàng) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Câu ví dụ <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                            </label>
                            <input type="text"
                                   name="entries[{{ $idx }}][example_sentence]"
                                   value="{{ $entry['example_sentence'] ?? '' }}"
                                   placeholder="Ví dụ: I eat an apple every day..."
                                   maxlength="2000"
                                   lang="{{ $deck->learningLanguage->code }}"
                                   dir="{{ $deck->learningLanguage->direction }}"
                                   class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Dịch câu ví dụ <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                            </label>
                            <input type="text"
                                   name="entries[{{ $idx }}][example_translation]"
                                   value="{{ $entry['example_translation'] ?? '' }}"
                                   placeholder="Ví dụ: Tôi ăn một quả táo mỗi ngày..."
                                   maxlength="2000"
                                   lang="{{ $deck->meaningLanguage->code }}"
                                   dir="{{ $deck->meaningLanguage->direction }}"
                                   class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Nút thêm khung từ mới -->
        <div class="flex items-center justify-center pt-2">
            <button type="button"
                    id="add-word-btn"
                    class="inline-flex items-center justify-center rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/40 px-6 py-3 text-sm font-bold text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300 transition w-full sm:w-auto cursor-pointer shadow-2xs">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                + Từ mới
            </button>
        </div>

        <!-- Thanh hành động cố định ở dưới -->
        <div class="rounded-3xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-500">
                Đang có <strong id="card-count" class="text-indigo-600 font-bold text-sm">{{ count($oldEntries) }}</strong> khung từ được chuẩn bị
            </div>

            <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                <a href="{{ route('decks.show', $deck) }}"
                   class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Hủy bỏ
                </a>

                <button type="submit"
                        id="submit-all-btn"
                        class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30 cursor-pointer">
                    Thêm mục từ vào bộ từ
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('word-cards-container');
    const addWordBtn = document.getElementById('add-word-btn');
    const cardCountEl = document.getElementById('card-count');
    const learningLangCode = @json($deck->learningLanguage->code);
    const learningLangDir = @json($deck->learningLanguage->direction);
    const meaningLangCode = @json($deck->meaningLanguage->code);
    const meaningLangDir = @json($deck->meaningLanguage->direction);
    const meaningLangName = @json($deck->meaningLanguage->name);

    let nextIndex = {{ count($oldEntries) }};

    function updateCardIndices() {
        const cards = container.querySelectorAll('.word-card');
        cards.forEach((card, idx) => {
            const numEl = card.querySelector('.card-number');
            if (numEl) numEl.textContent = idx + 1;

            const removeBtn = card.querySelector('.remove-card-btn');
            if (removeBtn) {
                removeBtn.style.display = cards.length <= 1 ? 'none' : 'inline-block';
            }
        });

        if (cardCountEl) {
            cardCountEl.textContent = cards.length;
        }
    }

    // Thêm khung từ mới
    addWordBtn.addEventListener('click', function () {
        const idx = nextIndex++;
        const currentCount = container.querySelectorAll('.word-card').length + 1;

        const cardHtml = `
            <div class="word-card rounded-3xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm hover:border-indigo-200 transition space-y-4 relative" data-index="${idx}">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs card-number font-mono">
                            ${currentCount}
                        </span>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                            Khung từ vựng
                        </span>
                    </div>

                    <button type="button"
                            class="remove-card-btn text-xs text-rose-500 hover:text-rose-700 font-medium px-2 py-1 rounded hover:bg-rose-50 transition cursor-pointer">
                        &times; Xóa khung
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-slate-800 mb-1">
                            Từ hoặc cụm từ <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="entries[${idx}][term]"
                               placeholder="Nhập từ mới..."
                               maxlength="255"
                               lang="${learningLangCode}"
                               dir="${learningLangDir}"
                               class="term-input w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-base font-bold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Phiên âm <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                        </label>
                        <input type="text"
                               name="entries[${idx}][pronunciation]"
                               placeholder="Phiên âm..."
                               maxlength="255"
                               lang="${learningLangCode}"
                               dir="${learningLangDir}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-mono text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Từ loại <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                        </label>
                        <input type="text"
                               name="entries[${idx}][part_of_speech]"
                               placeholder="n, v, adj..."
                               maxlength="100"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-800 mb-1">
                        Ý nghĩa <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                    </label>
                    <input type="text"
                           name="entries[${idx}][meaning]"
                           placeholder="Giải thích nghĩa bằng ${meaningLangName}..."
                           maxlength="2000"
                           lang="${meaningLangCode}"
                           dir="${meaningLangDir}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Câu ví dụ <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                        </label>
                        <input type="text"
                               name="entries[${idx}][example_sentence]"
                               placeholder="Câu ví dụ minh họa..."
                               maxlength="2000"
                               lang="${learningLangCode}"
                               dir="${learningLangDir}"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Dịch câu ví dụ <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                        </label>
                        <input type="text"
                               name="entries[${idx}][example_translation]"
                               placeholder="Bản dịch câu ví dụ..."
                               maxlength="2000"
                               lang="${meaningLangCode}"
                               dir="${meaningLangDir}"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', cardHtml);
        updateCardIndices();

        // Tự động focus vào ô term của khung mới tạo
        const lastCard = container.querySelector('.word-card:last-child');
        if (lastCard) {
            const input = lastCard.querySelector('.term-input');
            if (input) input.focus();
        }
    });

    // Xóa khung từ
    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-card-btn')) {
            const card = e.target.closest('.word-card');
            if (container.querySelectorAll('.word-card').length > 1) {
                card.remove();
                updateCardIndices();
            }
        }
    });

    updateCardIndices();

    // Ngăn chặn submit lặp
    const form = document.getElementById('batch-entry-form');
    if (form) {
        form.addEventListener('submit', function () {
            const btn = document.getElementById('submit-all-btn');
            if (btn && !btn.disabled) {
                setTimeout(() => {
                    btn.disabled = true;
                    btn.classList.add('opacity-75', 'cursor-not-allowed');
                    btn.textContent = 'Đang lưu từ mới...';
                }, 10);
            }
        });
    }
});
</script>
@endsection
