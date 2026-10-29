@extends('layouts.app')

@section('title', $session->mode->label() . ': ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-2xl space-y-6 pb-28 sm:pb-12">
    <!-- Thanh trên (Top Bar) -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">
                        {{ $session->mode->label() }}
                    </span>
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $session->stage === \App\Enums\StudyStage::Familiarization ? 'bg-sky-100 text-sky-800' : 'bg-purple-100 text-purple-800' }}">
                        {{ $session->stage->label() }}
                    </span>
                    @if (! $session->mode->isSrsModifying())
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 border border-blue-200">
                            Không đổi lịch SRS
                        </span>
                    @endif
                    <span class="text-xs text-slate-400">&bull;</span>
                    <span class="text-xs text-slate-500 font-medium truncate">
                        Nhóm {{ $currentChunkIndex + 1 }}/{{ $totalChunks }}
                    </span>
                </div>
                <h1 class="mt-1 text-sm sm:text-base font-bold text-slate-900 truncate">
                    {{ $deck->name }}
                </h1>
            </div>

            <div class="flex items-center space-x-2 shrink-0">
                <!-- Nút hướng dẫn phím tắt -->
                <button type="button"
                        onclick="toggleShortcutsModal()"
                        title="Xem phím tắt"
                        class="hidden sm:inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                    ⌨️ Phím tắt
                </button>

                <!-- Nút Tạm dừng -->
                <form method="POST" action="{{ route('study.session.pause', $session) }}">
                    @csrf
                    <button type="submit"
                            title="Tạm dừng phiên học để tiếp tục sau"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-2.5 sm:px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                        <svg class="sm:mr-1 h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">Tạm dừng</span>
                    </button>
                </form>

                <!-- Nút Kết thúc phiên -->
                <form method="POST" action="{{ route('study.session.abandon', $session) }}"
                      onsubmit="return confirm('Bạn có chắc muốn kết thúc phiên học này? Các nghĩa bạn đã hoàn thành sẽ được lưu lại an toàn.');">
                    @csrf
                    <button type="submit"
                            title="Kết thúc phiên và lưu các mục đã học"
                            class="inline-flex items-center rounded-xl border border-rose-200 bg-white px-2.5 sm:px-3 py-1.5 text-xs font-semibold text-rose-600 shadow-2xs hover:bg-rose-50 transition cursor-pointer">
                        <span class="hidden sm:inline">Kết thúc</span>
                        <span class="sm:hidden">✕</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Thanh tiến độ theo số nghĩa & câu hỏi -->
        <div class="mt-3">
            <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                <span>
                    @if ($session->stage === \App\Enums\StudyStage::Familiarization)
                        Làm quen: <span class="font-bold text-slate-800">{{ $familiarizedCount }}/{{ $totalItemsCount }}</span> nghĩa
                    @else
                        Luyện nhớ: <span class="font-bold text-purple-800">{{ $practicedCount }}/{{ $totalItemsCount }}</span> nghĩa
                    @endif
                </span>
                <span class="text-slate-400">
                    {{ round((($session->stage === \App\Enums\StudyStage::Familiarization ? $familiarizedCount : $practicedCount) / max(1, $totalItemsCount)) * 100) }}%
                </span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full transition-all duration-300 {{ $session->stage === \App\Enums\StudyStage::Familiarization ? 'bg-indigo-600' : 'bg-purple-600' }}"
                     style="width: {{ (($session->stage === \App\Enums\StudyStage::Familiarization ? $familiarizedCount : $practicedCount) / max(1, $totalItemsCount)) * 100 }}%">
                </div>
            </div>
        </div>

        <!-- Trạng thái lưu phía server -->
        <div id="saveStatusIndicator" class="mt-2 text-[11px] text-slate-400 flex items-center justify-end space-x-1">
            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            <span id="saveStatusText">Đã đồng bộ</span>
        </div>
    </div>

    <!-- Thông báo lỗi kết nối mạng / Lưu thất bại -->
    <div id="networkErrorAlert" class="hidden rounded-2xl border border-rose-300 bg-rose-50 p-4 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex items-center space-x-2 text-sm text-rose-800 font-semibold">
                <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Lưu thất bại do mất kết nối mạng. Câu trả lời của bạn được giữ an toàn.</span>
            </div>
            <button type="button" onclick="retrySubmit()" class="rounded-lg bg-rose-600 px-3 py-1 text-xs font-bold text-white hover:bg-rose-700 transition cursor-pointer">
                Thử lại
            </button>
        </div>
    </div>

    <!-- Vùng giữa: Nội dung Thẻ hoặc Câu hỏi -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        @if ($session->stage === \App\Enums\StudyStage::Familiarization)
            <!-- ================= GIAI ĐOẠN 1: LÀM QUEN TỪ MỚI ================= -->
            <div class="space-y-6">
                <!-- Nhãn yêu cầu -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Làm quen từ mới
                        </span>
                    </div>
                    <span class="text-xs text-slate-400">
                        Nhóm {{ $currentChunkIndex + 1 }} &bull; Đơn vị: 1 nghĩa
                    </span>
                </div>

                <!-- Phân cấp thị giác: Từ là nội dung nổi bật nhất -->
                <div class="text-center py-2">
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-indigo-950 font-serif"
                        dir="{{ $deck->learningLanguage->direction }}"
                        lang="{{ $deck->learningLanguage->code }}">
                        {{ $currentEntry->term }}
                    </h2>

                    @if ($currentEntry->pronunciation)
                        <div class="mt-2 text-sm sm:text-base font-mono text-slate-500">
                            /{{ $currentEntry->pronunciation }}/
                        </div>
                    @endif

                    <!-- Nút phát âm Web Speech API -->
                    <div class="mt-3 flex justify-center">
                        <button type="button"
                                onclick="speakWord('{{ addslashes($currentEntry->term) }}', '{{ $deck->learningLanguage->code }}')"
                                class="inline-flex items-center rounded-full bg-indigo-50 px-3.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition cursor-pointer">
                            <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                            Phát âm (Nghe lại)
                        </button>
                    </div>
                </div>

                <!-- Nghĩa ở ngay dưới từ vựng -->
                <div class="rounded-2xl bg-slate-50 p-5 border border-slate-100">
                    <div class="flex items-center space-x-2">
                        @if ($currentMeaning->part_of_speech)
                            <span class="inline-flex items-center rounded-md bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-800">
                                {{ $currentMeaning->part_of_speech }}
                            </span>
                        @endif
                        <span class="text-xs font-semibold text-slate-500">Ý nghĩa đang học:</span>
                    </div>

                    <div class="mt-2 text-lg sm:text-xl font-bold text-slate-900"
                         dir="{{ $deck->meaningLanguage->direction }}"
                         lang="{{ $deck->meaningLanguage->code }}">
                        {{ $currentMeaning->meaning ?: 'Chưa có mô tả ý nghĩa văn bản' }}
                    </div>

                    <!-- Cảnh báo nếu từ này có nhiều nghĩa khác để không nhầm lẫn -->
                    @php
                        $otherMeaningsCount = $currentEntry->meanings->count() - 1;
                    @endphp
                    @if ($otherMeaningsCount > 0)
                        <div class="mt-3 pt-3 border-t border-slate-200/60 text-xs text-slate-500 flex items-start space-x-1.5">
                            <span class="text-amber-600 font-bold">ℹ️</span>
                            <span>
                                Từ này có thêm <span class="font-semibold">{{ $otherMeaningsCount }}</span> nét nghĩa khác trong bộ từ. Bạn đang được tính tiến độ riêng cho nghĩa trên.
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Ví dụ tách thành khối dễ đọc kèm nút ẩn/hiện dịch -->
                @if ($currentMeaning->examples->isNotEmpty())
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Câu ví dụ ngữ cảnh
                            </div>
                            <button type="button"
                                    onclick="toggleTranslations()"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition cursor-pointer">
                                👁️ Ẩn/Hiện dịch ví dụ
                            </button>
                        </div>
                        <div class="space-y-2">
                            @foreach ($currentMeaning->examples as $ex)
                                <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs">
                                    <p class="text-sm font-medium text-slate-900 leading-relaxed"
                                       dir="{{ $deck->learningLanguage->direction }}"
                                       lang="{{ $deck->learningLanguage->code }}">
                                        {{ $ex->sentence }}
                                    </p>
                                    @if ($ex->translation)
                                        <p class="example-translation mt-1 text-xs text-slate-600 leading-relaxed"
                                           dir="{{ $deck->meaningLanguage->direction }}"
                                           lang="{{ $deck->meaningLanguage->code }}">
                                            {{ $ex->translation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Ghi chú chung nếu có -->
                @if ($currentEntry->note)
                    <div class="rounded-xl bg-amber-50/50 p-3 border border-amber-200/60 text-xs text-amber-900">
                        <span class="font-bold">Ghi chú:</span> {{ $currentEntry->note }}
                    </div>
                @endif
            </div>

        @else
            <!-- ================= GIAI ĐOẠN 2: LUYỆN NHỚ / ÔN TẬP ================= -->
            <div class="space-y-6">
                <!-- Nhãn yêu cầu câu hỏi -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-purple-500"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">
                            {{ $practiceQuestion['prompt'] ?? 'Luyện nhớ' }}
                        </span>
                    </div>
                    <span class="text-xs text-slate-400">
                        Nhóm {{ $currentChunkIndex + 1 }}
                    </span>
                </div>

                <!-- Banner Gợi ý (ẩn ban đầu, hiện khi bấm nút Gợi ý) -->
                <div id="hintDisplayBox" class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-medium text-amber-900">
                    <div class="flex items-start space-x-2">
                        <span class="text-amber-600 font-bold">💡 Gợi ý:</span>
                        <span id="hintDisplayText"></span>
                    </div>
                </div>

                <!-- 1. Dạng Thẻ ghi nhớ (Flashcard) -->
                @if (($practiceQuestion['type'] ?? '') === 'flashcard')
                    <div class="space-y-4">
                        <!-- Mặt trước thẻ -->
                        <div class="rounded-3xl border-2 border-indigo-200 bg-indigo-50/30 p-8 text-center space-y-3">
                            <span class="text-2xs uppercase tracking-wider font-bold text-indigo-600">Mặt trước</span>
                            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 font-serif">
                                {{ $practiceQuestion['front_text'] ?? $currentEntry->term }}
                            </h2>
                            @if (!empty($practiceQuestion['pronunciation']))
                                <div class="text-sm font-mono text-slate-500">/{{ $practiceQuestion['pronunciation'] }}/</div>
                            @endif
                            <div class="pt-2">
                                <button type="button"
                                        id="flipCardBtn"
                                        onclick="flipFlashcard()"
                                        class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer">
                                    🔄 Xem đáp án / Lật thẻ
                                </button>
                            </div>
                        </div>

                        <!-- Mặt sau thẻ (ẩn ban đầu, hiện sau khi lật) -->
                        <div id="flashcardBack" class="hidden rounded-3xl border-2 border-emerald-200 bg-emerald-50/30 p-8 text-center space-y-3">
                            <span class="text-2xs uppercase tracking-wider font-bold text-emerald-700">Mặt sau (Giải nghĩa)</span>
                            <div class="text-2xl font-bold text-slate-900">
                                {{ $practiceQuestion['back_text'] ?? $currentMeaning->meaning }}
                            </div>
                            @if (!empty($practiceQuestion['example_sentence']))
                                <div class="mt-3 pt-3 border-t border-emerald-200/60 text-xs text-slate-600 italic">
                                    "{{ $practiceQuestion['example_sentence'] }}"
                                    @if (!empty($practiceQuestion['example_translation']))
                                        <div class="not-italic text-slate-500 mt-0.5">({{ $practiceQuestion['example_translation'] }})</div>
                                    @endif
                                </div>
                            @endif
                            <div class="text-[11px] text-slate-400 italic mt-2">
                                * Kết quả là tự đánh giá của người học
                            </div>
                        </div>
                    </div>

                <!-- 2. Dạng Điền từ vào câu ví dụ (Fill in the blank) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'fill_in_the_blank')
                    <div class="space-y-4">
                        <div class="rounded-2xl bg-slate-50 p-6 text-center border border-slate-200">
                            <p class="text-lg font-medium text-slate-900 leading-relaxed italic" id="clozeDisplaySentence">
                                "{{ $practiceQuestion['cloze_sentence'] ?? '[ ______ ]' }}"
                            </p>
                            @if (!empty($practiceQuestion['example_translation']))
                                <p class="mt-2 text-xs text-slate-500">({{ $practiceQuestion['example_translation'] }})</p>
                            @endif
                        </div>

                        <div class="space-y-1.5">
                            <label for="practiceInputText" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                                Nhập từ vựng cần điền:
                            </label>
                            <input type="text"
                                   id="practiceInputText"
                                   autocomplete="off"
                                   autocorrect="off"
                                   spellcheck="false"
                                   placeholder="Gõ từ vào đây..."
                                   oninput="enablePracticeSubmit()"
                                   class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-base font-semibold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>

                <!-- 3. Dạng Ghép cặp (Matching Pairs) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'matching_pairs')
                    <div class="space-y-4">
                        <p class="text-xs text-slate-500">Bấm một từ ở cột trái rồi bấm nghĩa tương ứng ở cột phải:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Cột từ -->
                            <div class="space-y-2">
                                <div class="text-xs font-bold text-slate-600 uppercase">Cột Từ vựng</div>
                                @foreach ($practiceQuestion['pairs'] as $p)
                                    <button type="button"
                                            onclick="selectPairTerm('{{ $p['id'] }}', this)"
                                            id="pairTerm_{{ $p['id'] }}"
                                            class="pair-term-btn w-full text-left rounded-xl border-2 border-slate-200 bg-white p-3 text-sm font-bold text-slate-800 transition hover:border-indigo-400 cursor-pointer">
                                        {{ $p['term'] }}
                                    </button>
                                @endforeach
                            </div>
                            <!-- Cột nghĩa -->
                            <div class="space-y-2">
                                <div class="text-xs font-bold text-slate-600 uppercase">Cột Ý nghĩa</div>
                                @foreach ($practiceQuestion['pairs'] as $p)
                                    <button type="button"
                                            onclick="selectPairMeaning('{{ $p['id'] }}', this)"
                                            id="pairMeaning_{{ $p['id'] }}"
                                            class="pair-meaning-btn w-full text-left rounded-xl border-2 border-slate-200 bg-white p-3 text-sm font-semibold text-slate-800 transition hover:border-indigo-400 cursor-pointer">
                                        {{ $p['meaning'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                <!-- 4. Dạng Nghe và Viết (Listen and Type) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'listen_and_type')
                    <div class="space-y-6 text-center">
                        <div class="py-4">
                            <button type="button"
                                    onclick="speakWord('{{ addslashes($practiceQuestion['audio_term'] ?? $currentEntry->term) }}', '{{ $practiceQuestion['audio_lang'] ?? 'en' }}')"
                                    class="inline-flex items-center rounded-2xl bg-indigo-600 px-6 py-4 text-base font-bold text-white shadow-md hover:bg-indigo-500 transition cursor-pointer">
                                <svg class="mr-2 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                </svg>
                                🔊 Nghe phát âm từ vựng
                            </button>
                            <p class="text-xs text-slate-400 mt-2">Bấm nút trên để nghe và gõ lại đúng từ vựng</p>
                        </div>

                        <div class="space-y-1.5 text-left">
                            <label for="practiceInputText" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                                Nhập từ bạn vừa nghe được:
                            </label>
                            <input type="text"
                                   id="practiceInputText"
                                   autocomplete="off"
                                   autocorrect="off"
                                   autocapitalize="off"
                                   spellcheck="false"
                                   placeholder="Gõ từ bạn nghe được..."
                                   oninput="enablePracticeSubmit()"
                                   class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-base font-semibold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>

                <!-- 5. Dạng Nghe và Chọn (Listen and Choose) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'listen_and_choose')
                    <div class="space-y-6 text-center">
                        <div class="py-4">
                            <button type="button"
                                    onclick="speakWord('{{ addslashes($practiceQuestion['audio_term'] ?? $currentEntry->term) }}', '{{ $practiceQuestion['audio_lang'] ?? 'en' }}')"
                                    class="inline-flex items-center rounded-2xl bg-indigo-600 px-6 py-4 text-base font-bold text-white shadow-md hover:bg-indigo-500 transition cursor-pointer">
                                <svg class="mr-2 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                </svg>
                                🔊 Nghe phát âm từ vựng
                            </button>
                            <p class="text-xs text-slate-400 mt-2">Nghe phát âm và chọn nghĩa chính xác bên dưới</p>
                        </div>

                        <div class="space-y-3" id="practiceOptionsContainer">
                            @foreach ($practiceQuestion['options'] ?? [] as $idx => $opt)
                                <button type="button"
                                        data-option-id="{{ $opt['id'] }}"
                                        data-is-correct="{{ $opt['is_correct'] ? 'true' : 'false' }}"
                                        onclick="selectPracticeOption(this)"
                                        class="practice-option w-full text-left rounded-2xl border-2 border-slate-200 bg-white p-4 text-sm font-semibold text-slate-800 transition hover:border-indigo-300 hover:bg-slate-50/50 focus:outline-none cursor-pointer flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">
                                            {{ chr(65 + $idx) }}
                                        </span>
                                        <span class="text-sm sm:text-base">{{ $opt['text'] }}</span>
                                    </div>
                                    <span class="result-badge hidden text-xs font-bold px-2 py-0.5 rounded-md"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                <!-- 6. Dạng Sắp xếp chữ cái / mảnh ghép (Word Unscramble) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'word_unscramble')
                    <div class="space-y-6">
                        <div class="text-center py-2">
                            <span class="text-xs uppercase font-bold text-slate-500">Ý nghĩa cần ghép từ:</span>
                            <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">
                                {{ $practiceQuestion['question_text'] ?? $currentMeaning->meaning }}
                            </h3>
                        </div>

                        <!-- Vùng Đáp án (Answer Tray) -->
                        <div class="rounded-2xl border-2 border-dashed border-indigo-300 bg-indigo-50/30 p-4 min-h-[70px] flex flex-wrap items-center justify-center gap-2" id="unscrambleAnswerTray">
                            <span class="text-xs text-indigo-400 font-medium" id="trayPlaceholder">Bấm vào các ký tự bên dưới để ghép từ</span>
                        </div>

                        <!-- Vùng Mảnh ký tự (Tile Tray) -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Các mảnh ký tự:</span>
                                <button type="button" onclick="resetUnscramble()" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer">
                                    ↺ Làm lại
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-2" id="unscrambleTileTray">
                                @foreach ($practiceQuestion['tiles'] ?? [] as $tile)
                                    <button type="button"
                                            id="{{ $tile['id'] }}"
                                            data-letter="{{ $tile['letter'] }}"
                                            onclick="moveTileToAnswer(this)"
                                            class="unscramble-tile h-12 w-12 rounded-xl border-2 border-slate-300 bg-white text-lg font-bold text-slate-800 shadow-xs hover:border-indigo-500 hover:bg-indigo-50 transition flex items-center justify-center cursor-pointer">
                                        {{ $tile['letter'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                <!-- 7. Dạng Trắc nghiệm chọn nghĩa / chọn từ (Multiple Choice) hoặc mặc định -->
                @else
                    <div class="text-center py-2">
                        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-indigo-950 font-serif"
                            dir="{{ $deck->learningLanguage->direction }}"
                            lang="{{ $deck->learningLanguage->code }}">
                            {{ $practiceQuestion['question_text'] ?? ($practiceQuestion['term'] ?? $currentEntry->term) }}
                        </h2>
                        @if (!empty($practiceQuestion['pronunciation']))
                            <div class="mt-1 text-sm font-mono text-slate-500">
                                /{{ $practiceQuestion['pronunciation'] }}/
                            </div>
                        @endif
                        @if (!empty($practiceQuestion['part_of_speech']))
                            <div class="mt-2">
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700">
                                    {{ $practiceQuestion['part_of_speech'] }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Danh sách các lựa chọn xếp dọc trên mobile và PC -->
                    <div class="space-y-3" id="practiceOptionsContainer">
                        @foreach ($practiceQuestion['options'] ?? [] as $idx => $opt)
                            <button type="button"
                                    data-option-id="{{ $opt['id'] }}"
                                    data-is-correct="{{ $opt['is_correct'] ? 'true' : 'false' }}"
                                    onclick="selectPracticeOption(this)"
                                    class="practice-option w-full text-left rounded-2xl border-2 border-slate-200 bg-white p-4 text-sm font-semibold text-slate-800 transition hover:border-indigo-300 hover:bg-slate-50/50 focus:outline-none cursor-pointer flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">
                                        {{ chr(65 + $idx) }}
                                    </span>
                                    <span class="text-sm sm:text-base">{{ $opt['text'] }}</span>
                                </div>
                                <span class="result-badge hidden text-xs font-bold px-2 py-0.5 rounded-md"></span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Khối phản hồi đánh giá khách quan -->
                <div id="evaluationFeedback" class="hidden rounded-2xl p-4 text-sm font-semibold"></div>
            </div>
        @endif
    </div>

    <!-- Thanh thao tác dưới đáy màn hình (Bottom Action Bar) -->
    <div class="sticky bottom-4 z-20 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur-md">
        <div class="flex items-center justify-between gap-3">
            @if ($session->stage === \App\Enums\StudyStage::Familiarization)
                <!-- Giai đoạn Làm quen -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            id="difficultBtn"
                            onclick="toggleDifficult()"
                            title="Đánh dấu nghĩa này là khó để sau này ôn kỹ hơn"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                        <span id="difficultIcon" class="mr-1.5 text-slate-400">⭐</span>
                        <span id="difficultText">Đánh dấu khó</span>
                    </button>

                    <button type="button"
                            id="selfKnownBtn"
                            onclick="startQuickVerify()"
                            title="Tự khai báo đã biết nghĩa này (kiểm tra nhanh trước khi ghi nhận)"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 transition cursor-pointer">
                        <span class="mr-1.5 text-emerald-600">✓</span>
                        Tôi đã biết nghĩa này
                    </button>
                </div>

                <div>
                    <button type="button"
                            id="continueFamiliarizeBtn"
                            onclick="submitFamiliarization()"
                            class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/20 cursor-pointer">
                        <span id="submitBtnSpinner" class="hidden mr-2">
                            <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </span>
                        <span id="continueBtnText">Đã hiểu & Tiếp tục &rarr;</span>
                    </button>
                </div>

            @else
                <!-- Giai đoạn Luyện nhớ / Ôn tập -->
                @if (($practiceQuestion['type'] ?? '') === 'flashcard')
                    <!-- Thanh thao tác riêng cho Flashcard -->
                    <div id="flashcardActions" class="hidden w-full flex items-center justify-between gap-3">
                        <button type="button"
                                onclick="submitSelfAssessment('self_forgot')"
                                class="inline-flex-1 items-center justify-center rounded-xl border-2 border-rose-300 bg-rose-50 px-5 py-2.5 text-xs font-bold text-rose-800 hover:bg-rose-100 transition cursor-pointer">
                            ✗ Chưa nhớ
                        </button>
                        <button type="button"
                                onclick="submitSelfAssessment('self_remember')"
                                class="inline-flex-1 items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition cursor-pointer">
                            ✓ Đã nhớ
                        </button>
                    </div>
                @else
                    <div class="flex items-center space-x-2">
                        <button type="button"
                                onclick="skipPractice()"
                                id="skipBtn"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                            Bỏ qua
                        </button>

                        <button type="button"
                                onclick="requestHint()"
                                id="hintBtn"
                                class="inline-flex items-center rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition cursor-pointer">
                            💡 Gợi ý
                        </button>
                    </div>

                    <div>
                        <button type="button"
                                id="submitPracticeBtn"
                                onclick="submitPractice()"
                                disabled
                                class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-slate-300 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition focus:outline-none cursor-not-allowed">
                            <span id="practiceBtnSpinner" class="hidden mr-2">
                                <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </span>
                            <span id="submitPracticeBtnText">Kiểm tra</span>
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Modal Kiểm tra nhanh khi bấm "Tôi đã biết" -->
<div id="quickVerifyModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Xác thực: Tôi đã biết nghĩa này</h3>
            <button type="button" onclick="closeQuickVerifyModal()" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
        </div>
        <div id="quickVerifyContent" class="space-y-4 text-xs text-slate-600">
            <p id="quickVerifyPrompt" class="font-semibold text-slate-800 text-sm">Đang chuẩn bị câu hỏi...</p>
            <div id="quickVerifyOptions" class="space-y-2"></div>
        </div>
        <div id="quickVerifyFeedback" class="hidden rounded-xl p-3 text-xs font-semibold"></div>
    </div>
</div>

<!-- Modal hướng dẫn phím tắt -->
<div id="shortcutsModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900">Phím tắt thao tác nhanh</h3>
        <div class="text-xs text-slate-600 space-y-2">
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">1, 2, 3, 4</span>
                <span>Chọn phương án tương ứng A, B, C, D</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">Space (Cách)</span>
                <span>Lật thẻ flashcard / Tiếp tục</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">Enter</span>
                <span>Kiểm tra / Nộp câu trả lời</span>
            </div>
        </div>
        <p class="text-2xs text-slate-400">
            * Phím tắt tự động tạm khóa khi bạn đang gõ văn bản hoặc đang ghép ký tự bằng bộ gõ IME.
        </p>
        <div class="pt-2 text-right">
            <button type="button" onclick="toggleShortcutsModal()" class="rounded-xl bg-slate-900 px-4 py-1.5 text-xs font-semibold text-white cursor-pointer">
                Đã hiểu
            </button>
        </div>
    </div>
</div>

<script>
    const csrfToken = '{{ csrf_token() }}';
    const sessionId = {{ $session->id }};
    const currentItemId = {{ $currentItem->id }};
    const stage = '{{ $session->stage->value }}';
    const exerciseType = '{{ $practiceQuestion['type'] ?? '' }}';

    let isDifficult = {{ $currentItem->is_difficult ? 'true' : 'false' }};
    let isSelfKnown = {{ $currentItem->is_self_declared_known ? 'true' : 'false' }};
    let usedHint = false;
    let isSubmitting = false;
    let isEvaluated = false;
    let selectedOptionElement = null;

    // Ẩn / Hiện dịch ví dụ
    function toggleTranslations() {
        document.querySelectorAll('.example-translation').forEach(el => {
            el.classList.toggle('hidden');
        });
    }

    // Lật thẻ Flashcard
    function flipFlashcard() {
        document.getElementById('flashcardBack')?.classList.remove('hidden');
        document.getElementById('flashcardActions')?.classList.remove('hidden');
        document.getElementById('flipCardBtn')?.classList.add('hidden');
    }

    function submitSelfAssessment(resultType) {
        if (isSubmitting) return;
        setSubmittingState(true);
        updateSaveIndicator('Đang lưu...');

        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/practice`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ result: resultType, used_hint: usedHint })
        })
        .then(res => res.json())
        .then(() => {
            updateSaveIndicator('Đã lưu');
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            updateSaveIndicator('Lưu thất bại');
            showNetworkError();
            setSubmittingState(false);
        });
    }

    // Ghép cặp (Matching Pairs)
    let selectedTermId = null;
    let matchedPairsCount = 0;
    const totalMatchingPairs = {{ !empty($practiceQuestion['pairs']) ? count($practiceQuestion['pairs']) : 0 }};

    function selectPairTerm(pairId, btn) {
        if (btn.disabled) return;
        document.querySelectorAll('.pair-term-btn').forEach(b => {
            if (!b.disabled) b.classList.remove('border-indigo-600', 'bg-indigo-50');
        });
        btn.classList.add('border-indigo-600', 'bg-indigo-50');
        selectedTermId = pairId;
    }

    function selectPairMeaning(pairId, btn) {
        if (!selectedTermId || btn.disabled) return;

        // Gọi API kiểm tra cặp phía server
        fetch(`/study/sessions/${sessionId}/check-pair`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ term_id: selectedTermId, meaning_id: pairId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.is_correct) {
                // Khớp đúng cặp!
                const termBtn = document.getElementById(`pairTerm_${selectedTermId}`);
                if (termBtn) {
                    termBtn.disabled = true;
                    termBtn.className = 'w-full text-left rounded-xl border-2 border-emerald-500 bg-emerald-50 p-3 text-sm font-bold text-emerald-900 cursor-not-allowed';
                }
                btn.disabled = true;
                btn.className = 'w-full text-left rounded-xl border-2 border-emerald-500 bg-emerald-50 p-3 text-sm font-semibold text-emerald-900 cursor-not-allowed';

                matchedPairsCount++;
                selectedTermId = null;

                if (matchedPairsCount >= totalMatchingPairs) {
                    enablePracticeSubmit();
                    submitPracticeDirect('pass');
                }
            } else {
                // Sai cặp
                btn.classList.add('border-rose-400', 'bg-rose-50');
                setTimeout(() => {
                    btn.classList.remove('border-rose-400', 'bg-rose-50');
                }, 600);
            }
        })
        .catch(err => {
            console.error(err);
        });
    }

    // Sắp xếp chữ cái (Word Unscramble)
    let unscrambleAnswerTiles = [];
    function moveTileToAnswer(btn) {
        const char = btn.getAttribute('data-letter');
        const id = btn.id;

        btn.classList.add('hidden');
        unscrambleAnswerTiles.push({ id: id, letter: char });
        renderUnscrambleAnswer();
        enablePracticeSubmit();
    }

    function removeTileFromAnswer(index) {
        if (index < 0 || index >= unscrambleAnswerTiles.length) return;
        const removed = unscrambleAnswerTiles.splice(index, 1)[0];
        const tileBtn = document.getElementById(removed.id);
        if (tileBtn) tileBtn.classList.remove('hidden');
        renderUnscrambleAnswer();
    }

    function renderUnscrambleAnswer() {
        const tray = document.getElementById('unscrambleAnswerTray');
        const placeholder = document.getElementById('trayPlaceholder');
        if (!tray) return;

        if (unscrambleAnswerTiles.length === 0) {
            tray.innerHTML = '<span class="text-xs text-indigo-400 font-medium" id="trayPlaceholder">Bấm vào các ký tự bên dưới để ghép từ</span>';
            return;
        }

        tray.innerHTML = '';
        unscrambleAnswerTiles.forEach((t, idx) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'h-10 w-10 rounded-xl bg-indigo-600 text-white font-bold text-base shadow-xs hover:bg-rose-600 transition flex items-center justify-center cursor-pointer';
            b.innerText = t.letter;
            b.onclick = () => removeTileFromAnswer(idx);
            tray.appendChild(b);
        });
    }

    function resetUnscramble() {
        unscrambleAnswerTiles = [];
        document.querySelectorAll('.unscramble-tile').forEach(b => b.classList.remove('hidden'));
        renderUnscrambleAnswer();
    }

    function enablePracticeSubmit() {
        const submitBtn = document.getElementById('submitPracticeBtn');
        if (submitBtn && !isEvaluated) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bg-slate-300', 'cursor-not-allowed');
            submitBtn.classList.add('bg-purple-600', 'hover:bg-purple-700', 'cursor-pointer');
        }
    }

    function selectPracticeOption(btn) {
        if (isEvaluated || isSubmitting) return;

        document.querySelectorAll('.practice-option').forEach(el => {
            el.classList.remove('border-indigo-600', 'bg-indigo-50/50');
        });

        btn.classList.add('border-indigo-600', 'bg-indigo-50/50');
        selectedOptionElement = btn;
        enablePracticeSubmit();
    }

    function submitPractice() {
        if (isEvaluated) {
            window.location.reload();
            return;
        }

        let isCorrect = false;

        if (exerciseType === 'fill_in_the_blank' || exerciseType === 'listen_and_type') {
            const val = document.getElementById('practiceInputText')?.value.trim().toLowerCase();
            const correct = '{{ addslashes(mb_strtolower($currentEntry->term ?? "")) }}'.trim();
            isCorrect = (val === correct);
        } else if (exerciseType === 'word_unscramble') {
            const assembled = unscrambleAnswerTiles.map(t => t.letter).join('').toLowerCase();
            const correct = '{{ addslashes(mb_strtolower(str_replace(" ", "", $currentEntry->term ?? ""))) }}'.trim();
            isCorrect = (assembled === correct);
        } else if (selectedOptionElement) {
            isCorrect = selectedOptionElement.getAttribute('data-is-correct') === 'true';
        }

        submitPracticeDirect(isCorrect ? 'pass' : 'fail');
    }

    function submitPracticeDirect(resultType) {
        isEvaluated = true;
        setSubmittingState(true);
        updateSaveIndicator('Đang lưu...');

        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/practice`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ result: resultType, used_hint: usedHint })
        })
        .then(res => res.json())
        .then(data => {
            hideNetworkError();
            setSubmittingState(false);
            updateSaveIndicator('Đã lưu');

            const feedback = document.getElementById('evaluationFeedback');
            if (feedback) {
                if (resultType === 'pass') {
                    feedback.className = 'rounded-2xl p-4 text-sm font-semibold bg-emerald-50 border border-emerald-200 text-emerald-900';
                    feedback.innerHTML = '🎉 Chính xác! Bạn đã ghi nhớ rất tốt nét nghĩa này.';
                } else {
                    feedback.className = 'rounded-2xl p-4 text-sm font-semibold bg-rose-50 border border-rose-200 text-rose-900';
                    feedback.innerHTML = '✗ Chưa chính xác. Đáp án đúng là: <strong class="text-slate-900">{{ addslashes($currentEntry->term) }}</strong>.';
                }
                feedback.classList.remove('hidden');
            }

            // Nếu là cloze sentence, hiển thị câu đầy đủ
            const clozeSentence = document.getElementById('clozeDisplaySentence');
            if (clozeSentence && '{{ !empty($practiceQuestion["example_sentence"]) ? 1 : 0 }}') {
                clozeSentence.innerText = '{{ addslashes($practiceQuestion["example_sentence"] ?? "") }}';
            }

            const submitBtn = document.getElementById('submitPracticeBtn');
            const submitBtnText = document.getElementById('submitPracticeBtnText');
            if (submitBtn && submitBtnText) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-slate-300', 'bg-purple-600');
                submitBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-500', 'cursor-pointer');
                submitBtnText.innerText = 'Tiếp tục câu tiếp →';
            }
        })
        .catch(err => {
            console.error(err);
            isEvaluated = false;
            updateSaveIndicator('Lưu thất bại');
            showNetworkError();
            setSubmittingState(false);
        });
    }

    function submitFamiliarization() {
        if (isSubmitting) return;
        setSubmittingState(true);
        updateSaveIndicator('Đang lưu...');

        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/familiarize`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                is_difficult: isDifficult,
                is_self_declared_known: isSelfKnown
            })
        })
        .then(res => res.json())
        .then(() => {
            updateSaveIndicator('Đã lưu');
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            updateSaveIndicator('Lưu thất bại');
            showNetworkError();
            setSubmittingState(false);
        });
    }

    function skipPractice() {
        if (isSubmitting) return;
        setSubmittingState(true);
        updateSaveIndicator('Đang lưu...');

        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/practice`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ result: 'skip', used_hint: usedHint })
        })
        .then(res => res.json())
        .then(() => {
            updateSaveIndicator('Đã lưu');
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            updateSaveIndicator('Lưu thất bại');
            showNetworkError();
            setSubmittingState(false);
        });
    }

    function toggleDifficult() {
        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/toggle-difficult`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            isDifficult = data.is_difficult;
            const icon = document.getElementById('difficultIcon');
            const text = document.getElementById('difficultText');
            const btn = document.getElementById('difficultBtn');

            if (isDifficult) {
                icon.innerText = '⭐';
                icon.className = 'mr-1.5 text-amber-500 font-bold';
                text.innerText = 'Đã đánh dấu khó';
                btn.classList.add('border-amber-300', 'bg-amber-50', 'text-amber-800');
            } else {
                icon.innerText = '⭐';
                icon.className = 'mr-1.5 text-slate-400';
                text.innerText = 'Đánh dấu khó';
                btn.classList.remove('border-amber-300', 'bg-amber-50', 'text-amber-800');
            }
        });
    }

    function requestHint() {
        usedHint = true;
        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/hint`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.hint) {
                const box = document.getElementById('hintDisplayBox');
                const text = document.getElementById('hintDisplayText');
                if (box && text) {
                    text.innerText = data.hint;
                    box.classList.remove('hidden');
                }
            }
        });
    }

    // Quick verify for "Tôi đã biết"
    function startQuickVerify() {
        const modal = document.getElementById('quickVerifyModal');
        const prompt = document.getElementById('quickVerifyPrompt');
        const opts = document.getElementById('quickVerifyOptions');
        const feedback = document.getElementById('quickVerifyFeedback');

        modal?.classList.remove('hidden');
        feedback?.classList.add('hidden');

        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/quick-verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.question) {
                prompt.innerText = data.question.prompt;
                opts.innerHTML = '';
                data.question.options.forEach(o => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'w-full text-left rounded-xl border border-slate-200 bg-white p-3 text-xs font-semibold text-slate-800 hover:border-indigo-500 hover:bg-indigo-50 transition cursor-pointer';
                    b.innerText = o.text;
                    b.onclick = () => submitQuickVerifyAnswer(o.id);
                    opts.appendChild(b);
                });
            }
        });
    }

    function submitQuickVerifyAnswer(chosen) {
        const feedback = document.getElementById('quickVerifyFeedback');
        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/quick-verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ answer: chosen })
        })
        .then(res => res.json())
        .then(data => {
            if (feedback) {
                feedback.classList.remove('hidden');
                if (data.verified) {
                    feedback.className = 'rounded-xl p-3 text-xs font-semibold bg-emerald-50 text-emerald-900 border border-emerald-200';
                    feedback.innerText = data.feedback;
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    feedback.className = 'rounded-xl p-3 text-xs font-semibold bg-rose-50 text-rose-900 border border-rose-200';
                    feedback.innerText = data.feedback;
                    setTimeout(() => closeQuickVerifyModal(), 1800);
                }
            }
        });
    }

    function closeQuickVerifyModal() {
        document.getElementById('quickVerifyModal')?.classList.add('hidden');
    }

    function updateSaveIndicator(text) {
        const el = document.getElementById('saveStatusText');
        if (el) el.innerText = text;
    }

    function setSubmittingState(loading) {
        isSubmitting = loading;
        const spinner = document.getElementById('submitBtnSpinner') || document.getElementById('practiceBtnSpinner');
        if (spinner) spinner.classList.toggle('hidden', !loading);
    }

    function showNetworkError() {
        document.getElementById('networkErrorAlert')?.classList.remove('hidden');
    }

    function hideNetworkError() {
        document.getElementById('networkErrorAlert')?.classList.add('hidden');
    }

    function retrySubmit() {
        hideNetworkError();
        if (stage === 'familiarization') {
            submitFamiliarization();
        } else {
            submitPractice();
        }
    }

    function toggleShortcutsModal() {
        document.getElementById('shortcutsModal')?.classList.toggle('hidden');
    }

    // Xử lý phím tắt & Bảo vệ bộ gõ IME
    document.addEventListener('keydown', function (e) {
        if (e.isComposing || ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            return;
        }

        if (e.key === ' ' && stage === 'familiarization') {
            e.preventDefault();
            submitFamiliarization();
        } else if (e.key === ' ' && exerciseType === 'flashcard') {
            e.preventDefault();
            flipFlashcard();
        } else if (['1', '2', '3', '4'].includes(e.key)) {
            const idx = parseInt(e.key, 10) - 1;
            const options = document.querySelectorAll('.practice-option');
            if (options[idx]) {
                options[idx].click();
            }
        } else if (e.key === 'Enter') {
            const submitBtn = document.getElementById('submitPracticeBtn');
            if (submitBtn && !submitBtn.disabled) {
                e.preventDefault();
                submitPractice();
            }
        }
    });

    function speakWord(word, langCode) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            const u = new SpeechSynthesisUtterance(word);
            u.lang = langCode || 'en-US';
            window.speechSynthesis.speak(u);
        }
    }
</script>
@endsection
