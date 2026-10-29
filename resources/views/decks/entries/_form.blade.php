@php
    $isEdit = isset($entry);
    $maxMeanings = (int) config('polyvocab.max_meanings_per_entry', 20);
    $maxExamples = (int) config('polyvocab.max_examples_per_meaning', 10);

    $meaningsData = old('meanings', $isEdit ? $entry->meanings->map(function ($m) {
        return [
            'id' => $m->id,
            'part_of_speech' => $m->part_of_speech,
            'meaning' => $m->meaning,
            'examples' => $m->examples->map(function ($e) {
                return [
                    'id' => $e->id,
                    'sentence' => $e->sentence,
                    'translation' => $e->translation,
                ];
            })->toArray(),
        ];
    })->toArray() : [
        [
            'part_of_speech' => '',
            'meaning' => '',
            'examples' => [
                ['sentence' => '', 'translation' => '']
            ]
        ]
    ]);

    if (empty($meaningsData)) {
        $meaningsData = [
            [
                'part_of_speech' => '',
                'meaning' => '',
                'examples' => [
                    ['sentence' => '', 'translation' => '']
                ]
            ]
        ];
    }
@endphp

<div class="space-y-6">
    <!-- Phần 1: Thông tin cơ bản mục từ -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Từ hoặc cụm từ -->
        <div>
            <label for="term" class="block text-sm font-medium text-slate-900">
                Từ hoặc cụm từ <span class="text-rose-500">*</span>
                <span class="text-xs font-normal text-indigo-600 ml-1">({{ $deck->learningLanguage->name }})</span>
            </label>
            <div class="mt-1">
                <input type="text"
                       name="term"
                       id="term"
                       value="{{ old('term', $isEdit ? $entry->term : '') }}"
                       placeholder="Ví dụ: Hello, 食べる, 谢谢, Bonjour..."
                       maxlength="255"
                       required
                       lang="{{ $deck->learningLanguage->code }}"
                       dir="{{ $deck->learningLanguage->direction }}"
                       class="w-full rounded-xl border @error('term') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-base font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>
            @error('term')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phiên âm (Pronunciation) -->
        <div>
            <label for="pronunciation" class="block text-sm font-medium text-slate-900">
                Phiên âm <span class="text-xs text-slate-400 font-normal">(Tùy chọn: IPA, Pinyin, Romaji...)</span>
            </label>
            <div class="mt-1">
                <input type="text"
                       name="pronunciation"
                       id="pronunciation"
                       value="{{ old('pronunciation', $isEdit ? $entry->pronunciation : '') }}"
                       placeholder="Ví dụ: /həˈloʊ/, xièxie, taberu..."
                       maxlength="255"
                       lang="{{ $deck->learningLanguage->code }}"
                       dir="{{ $deck->learningLanguage->direction }}"
                       class="w-full rounded-xl border @error('pronunciation') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm font-mono text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>
            @error('pronunciation')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Ghi chú chung của mục từ -->
    <div>
        <label for="note" class="block text-sm font-medium text-slate-900">
            Ghi chú chung của mục từ <span class="text-xs text-slate-400 font-normal">(Tùy chọn, tối đa 2.000 ký tự)</span>
        </label>
        <div class="mt-1">
            <textarea name="note"
                      id="note"
                      rows="2"
                      maxlength="2000"
                      placeholder="Ghi chú ngữ cảnh, cách dùng đặc biệt, nguồn gốc từ..."
                      class="w-full rounded-xl border @error('note') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ old('note', $isEdit ? $entry->note : '') }}</textarea>
        </div>
        @error('note')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Phần 2: Danh sách ý nghĩa & Ví dụ -->
    <div class="border-t border-slate-100 pt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">
                    Danh sách ý nghĩa & Câu ví dụ
                </h3>
                <p class="text-xs text-slate-500">
                    Tối thiểu 1 nghĩa, tối đa {{ $maxMeanings }} nghĩa. Mỗi nghĩa tối đa {{ $maxExamples }} ví dụ.
                </p>
            </div>

            <button type="button"
                    id="add-meaning-btn"
                    class="inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50/60 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition cursor-pointer">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Thêm nghĩa mới
            </button>
        </div>

        @error('meanings')
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-700">
                {{ $message }}
            </div>
        @enderror

        <div id="meanings-container" class="space-y-6">
            @foreach ($meaningsData as $mIndex => $meaning)
                <div class="meaning-card rounded-2xl border border-slate-200 bg-slate-50/40 p-5 space-y-4 relative" data-meaning-index="{{ $mIndex }}">
                    @if (!empty($meaning['id']))
                        <input type="hidden" name="meanings[{{ $mIndex }}][id]" value="{{ $meaning['id'] }}">
                    @endif

                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center text-xs font-bold uppercase tracking-wider text-slate-700">
                            <span class="meaning-number mr-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-slate-700 font-mono text-xs">
                                {{ $loop->iteration }}
                            </span>
                            Ý nghĩa
                        </span>

                        <button type="button"
                                class="remove-meaning-btn text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 rounded hover:bg-rose-50 transition cursor-pointer">
                            Xóa nghĩa này
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <!-- Từ loại -->
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Từ loại <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                            </label>
                            <input type="text"
                                   name="meanings[{{ $mIndex }}][part_of_speech]"
                                   value="{{ $meaning['part_of_speech'] ?? '' }}"
                                   placeholder="Danh từ, Động từ..."
                                   maxlength="100"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @error("meanings.{$mIndex}.part_of_speech")
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nghĩa -->
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Nội dung ý nghĩa <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                                <span class="text-xs font-normal text-indigo-600 ml-1">({{ $deck->meaningLanguage->name }})</span>
                            </label>
                            <textarea name="meanings[{{ $mIndex }}][meaning]"
                                      rows="2"
                                      maxlength="2000"
                                      placeholder="Giải thích nghĩa bằng {{ $deck->meaningLanguage->name }} (tùy chọn)..."
                                      lang="{{ $deck->meaningLanguage->code }}"
                                      dir="{{ $deck->meaningLanguage->direction }}"
                                      class="w-full rounded-xl border @error("meanings.{$mIndex}.meaning") border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ $meaning['meaning'] ?? '' }}</textarea>
                            @error("meanings.{$mIndex}.meaning")
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Khối danh sách ví dụ của nghĩa này -->
                    <div class="mt-4 border-t border-slate-200/60 pt-4 bg-white rounded-xl p-4 border">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-slate-700 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Câu ví dụ & Bản dịch
                            </span>

                            <button type="button"
                                    class="add-example-btn inline-flex items-center text-xs text-indigo-600 hover:text-indigo-800 font-medium cursor-pointer">
                                + Thêm ví dụ
                            </button>
                        </div>

                        <div class="examples-container space-y-3">
                            @php
                                $examples = $meaning['examples'] ?? [];
                            @endphp
                            @forelse ($examples as $eIndex => $example)
                                <div class="example-row rounded-xl bg-slate-50 p-3 border border-slate-200/80 relative" data-example-index="{{ $eIndex }}">
                                    @if (!empty($example['id']))
                                        <input type="hidden" name="meanings[{{ $mIndex }}][examples][{{ $eIndex }}][id]" value="{{ $example['id'] }}">
                                    @endif

                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs text-slate-500 font-mono">Ví dụ #<span class="example-number">{{ $loop->iteration }}</span></span>
                                        <button type="button" class="remove-example-btn text-xs text-rose-500 hover:text-rose-700 cursor-pointer">
                                            Xóa ví dụ
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        <div>
                                            <input type="text"
                                                   name="meanings[{{ $mIndex }}][examples][{{ $eIndex }}][sentence]"
                                                   value="{{ $example['sentence'] ?? '' }}"
                                                   placeholder="Câu ví dụ bằng {{ $deck->learningLanguage->name }}..."
                                                   maxlength="2000"
                                                   lang="{{ $deck->learningLanguage->code }}"
                                                   dir="{{ $deck->learningLanguage->direction }}"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                            @error("meanings.{$mIndex}.examples.{$eIndex}.sentence")
                                                <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <input type="text"
                                                   name="meanings[{{ $mIndex }}][examples][{{ $eIndex }}][translation]"
                                                   value="{{ $example['translation'] ?? '' }}"
                                                   placeholder="Bản dịch bằng {{ $deck->meaningLanguage->name }} (tùy chọn)..."
                                                   maxlength="2000"
                                                   lang="{{ $deck->meaningLanguage->code }}"
                                                   dir="{{ $deck->meaningLanguage->direction }}"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                            @error("meanings.{$mIndex}.examples.{$eIndex}.translation")
                                                <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-2 text-xs text-slate-400 no-examples-hint">
                                    Chưa có ví dụ nào cho nghĩa này.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Nút hành động -->
    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
        <a href="{{ $isEdit ? route('decks.entries.show', [$deck, $entry]) : route('decks.show', $deck) }}"
           class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            Hủy bỏ
        </a>

        <button type="submit"
                id="submit-entry-btn"
                class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30 cursor-pointer">
            <span class="btn-text">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm mục từ' }}</span>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const meaningsContainer = document.getElementById('meanings-container');
    const addMeaningBtn = document.getElementById('add-meaning-btn');
    const maxMeanings = {{ $maxMeanings }};
    const maxExamples = {{ $maxExamples }};
    const learningLangCode = @json($deck->learningLanguage->code);
    const learningLangDir = @json($deck->learningLanguage->direction);
    const learningLangName = @json($deck->learningLanguage->name);
    const meaningLangCode = @json($deck->meaningLanguage->code);
    const meaningLangDir = @json($deck->meaningLanguage->direction);
    const meaningLangName = @json($deck->meaningLanguage->name);

    let nextMeaningIndex = {{ count($meaningsData) }};

    function updateMeaningNumbers() {
        const cards = meaningsContainer.querySelectorAll('.meaning-card');
        cards.forEach((card, index) => {
            const numEl = card.querySelector('.meaning-number');
            if (numEl) numEl.textContent = (index + 1);

            const removeBtn = card.querySelector('.remove-meaning-btn');
            if (removeBtn) {
                if (cards.length <= 1) {
                    removeBtn.style.display = 'none';
                } else {
                    removeBtn.style.display = 'inline-block';
                }
            }
        });

        if (cards.length >= maxMeanings) {
            addMeaningBtn.disabled = true;
            addMeaningBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            addMeaningBtn.disabled = false;
            addMeaningBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    function updateExampleNumbers(card) {
        const examples = card.querySelectorAll('.example-row');
        examples.forEach((row, idx) => {
            const numEl = row.querySelector('.example-number');
            if (numEl) numEl.textContent = (idx + 1);
        });

        const addExBtn = card.querySelector('.add-example-btn');
        if (addExBtn) {
            if (examples.length >= maxExamples) {
                addExBtn.disabled = true;
                addExBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                addExBtn.disabled = false;
                addExBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Thêm nghĩa mới
    addMeaningBtn.addEventListener('click', function () {
        const currentCount = meaningsContainer.querySelectorAll('.meaning-card').length;
        if (currentCount >= maxMeanings) return;

        const mIdx = nextMeaningIndex++;
        const cardHtml = `
            <div class="meaning-card rounded-2xl border border-slate-200 bg-slate-50/40 p-5 space-y-4 relative" data-meaning-index="${mIdx}">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center text-xs font-bold uppercase tracking-wider text-slate-700">
                        <span class="meaning-number mr-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-slate-700 font-mono text-xs">
                            ${currentCount + 1}
                        </span>
                        Ý nghĩa
                    </span>

                    <button type="button"
                            class="remove-meaning-btn text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 rounded hover:bg-rose-50 transition cursor-pointer">
                        Xóa nghĩa này
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Từ loại <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                        </label>
                        <input type="text"
                               name="meanings[${mIdx}][part_of_speech]"
                               placeholder="Danh từ, Động từ..."
                               maxlength="100"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Nội dung ý nghĩa <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                            <span class="text-xs font-normal text-indigo-600 ml-1">(${meaningLangName})</span>
                        </label>
                        <textarea name="meanings[${mIdx}][meaning]"
                                  rows="2"
                                  maxlength="2000"
                                  placeholder="Giải thích nghĩa bằng ${meaningLangName} (tùy chọn)..."
                                  lang="${meaningLangCode}"
                                  dir="${meaningLangDir}"
                                  class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                    </div>
                </div>

                <div class="mt-4 border-t border-slate-200/60 pt-4 bg-white rounded-xl p-4 border">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Câu ví dụ & Bản dịch
                        </span>

                        <button type="button"
                                class="add-example-btn inline-flex items-center text-xs text-indigo-600 hover:text-indigo-800 font-medium cursor-pointer">
                            + Thêm ví dụ
                        </button>
                    </div>

                    <div class="examples-container space-y-3">
                        <div class="example-row rounded-xl bg-slate-50 p-3 border border-slate-200/80 relative" data-example-index="0">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-slate-500 font-mono">Ví dụ #<span class="example-number">1</span></span>
                                <button type="button" class="remove-example-btn text-xs text-rose-500 hover:text-rose-700 cursor-pointer">
                                    Xóa ví dụ
                                </button>
                            </div>

                            <div class="space-y-2">
                                <div>
                                    <input type="text"
                                           name="meanings[${mIdx}][examples][0][sentence]"
                                           placeholder="Câu ví dụ bằng ${learningLangName}..."
                                           maxlength="2000"
                                           lang="${learningLangCode}"
                                           dir="${learningLangDir}"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div>
                                    <input type="text"
                                           name="meanings[${mIdx}][examples][0][translation]"
                                           placeholder="Bản dịch bằng ${meaningLangName} (tùy chọn)..."
                                           maxlength="2000"
                                           lang="${meaningLangCode}"
                                           dir="${meaningLangDir}"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        meaningsContainer.insertAdjacentHTML('beforeend', cardHtml);
        updateMeaningNumbers();
    });

    // Event delegation cho Xóa nghĩa, Thêm ví dụ, Xóa ví dụ
    meaningsContainer.addEventListener('click', function (e) {
        // Xóa nghĩa
        if (e.target.closest('.remove-meaning-btn')) {
            const card = e.target.closest('.meaning-card');
            if (meaningsContainer.querySelectorAll('.meaning-card').length > 1) {
                card.remove();
                updateMeaningNumbers();
            }
            return;
        }

        // Thêm ví dụ
        if (e.target.closest('.add-example-btn')) {
            const card = e.target.closest('.meaning-card');
            const mIdx = card.getAttribute('data-meaning-index');
            const exContainer = card.querySelector('.examples-container');
            const currentExCount = exContainer.querySelectorAll('.example-row').length;

            if (currentExCount >= maxExamples) return;

            const eIdx = currentExCount;
            const exHtml = `
                <div class="example-row rounded-xl bg-slate-50 p-3 border border-slate-200/80 relative" data-example-index="${eIdx}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-slate-500 font-mono">Ví dụ #<span class="example-number">${currentExCount + 1}</span></span>
                        <button type="button" class="remove-example-btn text-xs text-rose-500 hover:text-rose-700 cursor-pointer">
                            Xóa ví dụ
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div>
                            <input type="text"
                                   name="meanings[${mIdx}][examples][${eIdx}][sentence]"
                                   placeholder="Câu ví dụ bằng ${learningLangName}..."
                                   maxlength="2000"
                                   lang="${learningLangCode}"
                                   dir="${learningLangDir}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <input type="text"
                                   name="meanings[${mIdx}][examples][${eIdx}][translation]"
                                   placeholder="Bản dịch bằng ${meaningLangName} (tùy chọn)..."
                                   maxlength="2000"
                                   lang="${meaningLangCode}"
                                   dir="${meaningLangDir}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                </div>
            `;

            const hint = exContainer.querySelector('.no-examples-hint');
            if (hint) hint.remove();

            exContainer.insertAdjacentHTML('beforeend', exHtml);
            updateExampleNumbers(card);
            return;
        }

        // Xóa ví dụ
        if (e.target.closest('.remove-example-btn')) {
            const row = e.target.closest('.example-row');
            const card = row.closest('.meaning-card');
            row.remove();
            updateExampleNumbers(card);
            return;
        }
    });

    // Khởi tạo ban đầu
    updateMeaningNumbers();
    meaningsContainer.querySelectorAll('.meaning-card').forEach(updateExampleNumbers);

    // Ngăn chặn submit form lặp
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function () {
            const btn = document.getElementById('submit-entry-btn');
            if (btn && !btn.disabled) {
                setTimeout(() => {
                    btn.disabled = true;
                    btn.classList.add('opacity-75', 'cursor-not-allowed');
                    const textSpan = btn.querySelector('.btn-text');
                    if (textSpan) textSpan.textContent = 'Đang lưu...';
                }, 10);
            }
        });
    }
});
</script>

