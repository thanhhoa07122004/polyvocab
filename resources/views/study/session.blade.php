@extends('layouts.app')

@section('title', $session->mode->label() . ': ' . $deck->name . ' | ' . config('app.name'))

@section('content')
@php
    $allSortedItems = $session->items->sortBy('sort_order')->values();
    $currentIndex = $allSortedItems->search(fn ($it) => $it->id === $currentItem->id);
    $currentNumber = ($currentIndex !== false ? $currentIndex + 1 : 1);
    $backRaw = $practiceQuestion['back_text'] ?? ($currentMeaning->meaning ?? '');
@endphp

<div class="mx-auto max-w-2xl space-y-2.5 sm:space-y-3 pb-2 sm:pb-4">
    <!-- Thanh trên (Top Bar) -->
    <div class="rounded-2xl border border-slate-200 bg-white px-3.5 py-2 sm:px-4 sm:py-2.5 shadow-2xs">
        <div class="flex items-center justify-between gap-2">
            <div class="min-w-0 flex items-center gap-1.5 sm:gap-2">
                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700 shrink-0">
                    {{ $session->mode->label() }}
                </span>
                @if ($session->mode === \App\Enums\StudyMode::LearnNew)
                    <span class="hidden sm:inline-flex items-center rounded-md bg-sky-100 px-2 py-0.5 text-2xs font-semibold text-sky-800 shrink-0">
                        Làm quen
                    </span>
                @else
                    @if ($isRetrying ?? false)
                        <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-2xs font-semibold text-amber-800 shrink-0">
                            ↺ Thử lại
                        </span>
                    @endif
                @endif
                <h1 class="text-xs sm:text-sm font-bold text-slate-900 truncate" title="{{ $deck->name }}">
                    {{ $deck->name }}
                </h1>
            </div>

            <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0">
                {{-- Toggle tự chuyển câu khi trả lời đúng (chỉ hiện trong Luyện tập / Ôn tập) --}}
                @if ($session->mode !== \App\Enums\StudyMode::LearnNew && ($practiceQuestion['type'] ?? '') !== 'flashcard')
                    <div class="hidden sm:flex items-center space-x-1 text-2xs text-slate-600 bg-slate-50 px-2 py-1 rounded-lg border border-slate-200">
                        <label class="inline-flex items-center cursor-pointer select-none space-x-1">
                            <input type="checkbox" id="autoAdvanceToggle" onchange="toggleAutoAdvanceSetting(this.checked)" class="rounded text-purple-600 focus:ring-purple-500 cursor-pointer h-3 w-3">
                            <span>Tự chuyển khi đúng</span>
                        </label>
                    </div>
                @endif

                <!-- Nút hướng dẫn phím tắt -->
                <button type="button"
                        onclick="toggleShortcutsModal()"
                        title="Xem phím tắt"
                        class="hidden sm:inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                    ⌨️
                </button>

                <!-- Nút Tạm dừng -->
                <form method="POST" action="{{ route('study.session.pause', $session) }}">
                    @csrf
                    <button type="submit"
                            title="Tạm dừng để học tiếp sau"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2 sm:px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                        <svg class="sm:mr-1 h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">Tạm dừng</span>
                    </button>
                </form>

                <!-- Nút Kết thúc phiên -->
                <form method="POST" action="{{ route('study.session.abandon', $session) }}"
                      onsubmit="return confirm('Kết quả đã lưu vẫn được giữ an toàn. Phần chưa học sẽ được trả về danh sách để bạn chọn lại.');">
                    @csrf
                    <button type="submit"
                            title="Kết thúc lần học này"
                            class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2 sm:px-2.5 py-1 text-xs font-semibold text-rose-600 shadow-2xs hover:bg-rose-50 transition cursor-pointer">
                        <span class="hidden sm:inline">Kết thúc</span>
                        <span class="sm:hidden">✕</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Thanh tiến độ & trạng thái đồng bộ gộp chung hàng gọn gàng -->
        <div class="mt-2 flex items-center gap-3">
            <div class="flex-1">
                <div class="flex items-center justify-between text-2xs text-slate-500 mb-1">
                    <span>
                        @if ($session->mode === \App\Enums\StudyMode::LearnNew)
                            Đã xem: <strong class="text-slate-800">{{ $familiarizedCount }}/{{ $totalItemsCount }}</strong> (Thẻ {{ $currentNumber }}/{{ $totalItemsCount }})
                        @else
                            Đã làm: <strong class="text-purple-800">{{ $practicedCount }}/{{ $totalItemsCount }}</strong> (Câu {{ $currentNumber }}/{{ $totalItemsCount }})
                        @endif
                    </span>
                    <span class="text-slate-400 font-medium">
                        {{ round((($session->mode === \App\Enums\StudyMode::LearnNew ? $familiarizedCount : $practicedCount) / max(1, $totalItemsCount)) * 100) }}%
                    </span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full transition-all duration-300 {{ $session->mode === \App\Enums\StudyMode::LearnNew ? 'bg-indigo-600' : 'bg-purple-600' }}"
                         style="width: {{ (($session->mode === \App\Enums\StudyMode::LearnNew ? $familiarizedCount : $practicedCount) / max(1, $totalItemsCount)) * 100 }}%">
                    </div>
                </div>
            </div>

            <!-- Trạng thái lưu phía server -->
            <div id="saveStatusIndicator" class="text-2xs text-slate-400 flex items-center space-x-1 shrink-0 pt-2">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span id="saveStatusText" class="hidden sm:inline">Đã đồng bộ</span>
            </div>
        </div>
    </div>

    <!-- Thông báo lỗi kết nối mạng / Lưu thất bại -->
    <div id="networkErrorAlert" class="hidden rounded-xl border border-rose-300 bg-rose-50 p-3 shadow-2xs">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 text-xs text-rose-800 font-semibold">
                <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Chưa lưu được câu trả lời. Bạn hãy bấm Thử lại nhé.</span>
            </div>
            <button type="button" onclick="retrySubmit()" class="rounded-lg bg-rose-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-rose-700 transition cursor-pointer">
                Thử lại
            </button>
        </div>
    </div>

    <!-- Vùng giữa: Nội dung Thẻ Làm quen hoặc Bài Luyện tập -->
    <div class="rounded-2xl border border-slate-200 bg-white p-3.5 sm:p-4.5 shadow-2xs">
        @if ($session->mode === \App\Enums\StudyMode::LearnNew)
            <!-- ================= GIAI ĐOẠN 1: LÀM QUEN TỪ MỚI ================= -->
            <div class="space-y-3">
                <!-- Nhãn đầu thẻ -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Thẻ làm quen từ mới
                        </span>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">
                        Từ {{ $currentNumber }}/{{ $totalItemsCount }}
                    </span>
                </div>

                <!-- Từ vựng nổi bật -->
                <div class="text-center py-1">
                    <div class="flex items-center justify-center gap-2">
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-indigo-950 font-serif"
                            dir="{{ $deck->learningLanguage->direction }}"
                            lang="{{ $deck->learningLanguage->code }}">
                            {{ $currentEntry->term }}
                        </h2>
                        <button type="button"
                                onclick="speakWord('{{ addslashes($currentEntry->term) }}', '{{ $deck->learningLanguage->code }}')"
                                title="Nghe phát âm"
                                class="rounded-full p-1 text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 transition cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                        </button>
                    </div>

                    @if ($currentEntry->pronunciation)
                        <div class="mt-0.5 text-xs sm:text-sm font-mono text-slate-500">
                            /{{ $currentEntry->pronunciation }}/
                        </div>
                    @endif
                </div>

                <!-- Nghĩa của từ -->
                <div class="rounded-xl bg-slate-50 p-3 sm:p-3.5 border border-slate-100">
                    <div class="flex items-center space-x-2">
                        @if ($currentMeaning->part_of_speech)
                            <span class="inline-flex items-center rounded-md bg-indigo-100 px-2 py-0.5 text-2xs font-bold text-indigo-800">
                                {{ $currentMeaning->part_of_speech }}
                            </span>
                        @endif
                        <span class="text-2xs font-semibold text-slate-500">Nghĩa của từ:</span>
                    </div>

                    <div class="mt-1 text-base sm:text-lg font-bold text-slate-900"
                         dir="{{ $deck->meaningLanguage->direction }}"
                         lang="{{ $deck->meaningLanguage->code }}">
                        {{ $currentMeaning->meaning ?: 'Chưa có mô tả ý nghĩa' }}
                    </div>

                    @php
                        $otherMeaningsCount = $currentEntry->meanings->count() - 1;
                    @endphp
                    @if ($otherMeaningsCount > 0)
                        <div class="mt-1.5 pt-1.5 border-t border-slate-200/60 text-2xs text-slate-500 flex items-start space-x-1">
                            <span class="text-amber-600 font-bold">ℹ️</span>
                            <span>Từ này còn <strong class="font-semibold">{{ $otherMeaningsCount }}</strong> nét nghĩa khác trong bộ từ.</span>
                        </div>
                    @endif
                </div>

                <!-- Câu ví dụ ngữ cảnh (tối đa 1-2 ví dụ ngắn gọn) -->
                @if ($currentMeaning->examples->isNotEmpty())
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-2xs">
                            <span class="font-bold uppercase tracking-wider text-slate-400">Ví dụ ngữ cảnh:</span>
                            <button type="button"
                                    onclick="toggleTranslations()"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800 transition cursor-pointer">
                                👁️ Ẩn/Hiện dịch
                            </button>
                        </div>
                        <div class="space-y-1.5 max-h-[105px] overflow-y-auto pr-1">
                            @foreach ($currentMeaning->examples as $ex)
                                <div class="rounded-lg border border-slate-200 bg-white p-2 text-xs shadow-2xs">
                                    <p class="font-medium text-slate-900 leading-snug"
                                       dir="{{ $deck->learningLanguage->direction }}"
                                       lang="{{ $deck->learningLanguage->code }}">
                                        {{ $ex->sentence }}
                                    </p>
                                    @if ($ex->translation)
                                        <p class="example-translation mt-0.5 text-2xs text-slate-500 leading-snug"
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

                @if ($currentEntry->note)
                    <div class="rounded-lg bg-amber-50/50 p-2 border border-amber-200/60 text-2xs text-amber-900">
                        <span class="font-bold">Ghi chú:</span> {{ $currentEntry->note }}
                    </div>
                @endif
            </div>

        @else
            <!-- ================= GIAI ĐOẠN 2: BÀI TẬP LUYỆN NHỚ ================= -->
            <div class="space-y-2.5 sm:space-y-3">
                <!-- Nhãn dạng bài -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-purple-500"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">
                            {{ $practiceQuestion['prompt'] ?? 'Luyện nhớ' }}
                        </span>
                        @if (!empty($practiceQuestion['is_ai_generated']))
                            <span class="inline-flex items-center rounded-full bg-purple-100 text-purple-800 px-2 py-0.5 text-2xs font-bold border border-purple-200">
                                ✨ Câu do AI tạo
                            </span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-400 font-medium">
                        Câu {{ $currentNumber }}/{{ $totalItemsCount }}
                    </span>
                </div>

                <!-- Banner Gợi ý -->
                <div id="hintDisplayBox" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-2.5 text-xs font-medium text-amber-900">
                    <div class="flex items-start space-x-2">
                        <span class="text-amber-600 font-bold shrink-0">💡 Gợi ý:</span>
                        <span id="hintDisplayText"></span>
                    </div>
                </div>

                <!-- 1. Dạng Thẻ ghi nhớ (Flashcard 3D Flip) -->
                @if (($practiceQuestion['type'] ?? '') === 'flashcard')
                    <div class="space-y-2">
                        {{-- Hàng điều khiển & Hướng dẫn lật thẻ --}}
                        <div class="flex items-center justify-between text-xs max-w-md mx-auto px-1">
                            <span class="text-slate-500 font-medium flex items-center space-x-1 text-2xs sm:text-xs">
                                <span>💡 Bấm thẻ hoặc phím Cách để lật</span>
                            </span>
                            <button type="button"
                                    id="flipDirectionBtn"
                                    onclick="toggleFlashcardDirection(event)"
                                    class="inline-flex items-center space-x-1 font-semibold text-indigo-600 hover:text-indigo-800 transition cursor-pointer text-2xs sm:text-xs">
                                <span>⇄ Đổi chiều:</span>
                                <span id="directionLabel" class="font-bold underline">Từ &rarr; Nghĩa</span>
                            </button>
                        </div>

                        <!-- Khung 3D Flashcard Scene (Tỷ lệ cân đối, xoay ngang trục giữa) -->
                        <div class="flashcard-3d-scene max-w-md mx-auto select-none">
                            <div id="flashcardElement"
                                 class="flashcard-3d-card"
                                 onclick="toggleFlashcardFlip()"
                                 role="button"
                                 tabindex="0"
                                 title="Bấm vào thẻ để lật mặt trước / mặt sau">
                                
                                <!-- Mặt trước thẻ (Front Face) -->
                                <div class="flashcard-3d-face flashcard-3d-front rounded-2xl border border-indigo-200/90 bg-white p-3.5 sm:p-4 text-center shadow-xs hover:border-indigo-400 transition-colors duration-200">
                                    <div class="flex items-center justify-between w-full">
                                        <span class="text-2xs uppercase tracking-wider font-bold text-indigo-700 bg-indigo-50 border border-indigo-100/80 px-2.5 py-0.5 rounded-full" id="cardFrontSideLabel">
                                            Mặt trước
                                        </span>
                                        <button type="button"
                                                onclick="event.stopPropagation(); playAudioOrSpeak('{{ $currentEntry->audio_url ?? '' }}', '{{ addslashes($currentEntry->term) }}', '{{ $deck->learningLanguage->code ?? 'en' }}')"
                                                title="Nghe phát âm"
                                                class="rounded-full p-1 text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="my-auto py-1 space-y-1">
                                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight font-serif" id="cardFrontText">
                                            {{ $practiceQuestion['front_text'] ?? $currentEntry->term }}
                                        </h2>
                                        @if (!empty($practiceQuestion['pronunciation']))
                                            <div class="text-xs sm:text-sm font-mono text-slate-400" id="cardFrontPronunciation">
                                                /{{ $practiceQuestion['pronunciation'] }}/
                                            </div>
                                        @endif
                                    </div>

                                    <div class="inline-flex items-center justify-center space-x-1 text-2xs font-semibold text-indigo-600 bg-indigo-50/70 py-1 px-3 rounded-full border border-indigo-100 shadow-2xs self-center">
                                        <span>🔄 Bấm thẻ để lật xem đáp án</span>
                                    </div>
                                </div>

                                <!-- Mặt sau thẻ (Back Face) -->
                                <div class="flashcard-3d-face flashcard-3d-back rounded-2xl border border-emerald-200/90 bg-white p-3.5 sm:p-4 text-center shadow-xs hover:border-emerald-400 transition-colors duration-200">
                                    @php
                                        $backRaw = $practiceQuestion['back_text'] ?? $currentMeaning->meaning ?? '';
                                        $hasMeaningText = !empty(trim($backRaw)) && $backRaw !== 'Chưa có mô tả ý nghĩa';
                                    @endphp
                                    <div class="flex items-center justify-between w-full">
                                        <span class="text-2xs uppercase tracking-wider font-bold text-emerald-800 bg-emerald-50 border border-emerald-100/80 px-2.5 py-0.5 rounded-full" id="cardBackSideLabel">
                                            Mặt sau (Giải nghĩa)
                                        </span>
                                        @if ($currentEntry->hasAudio())
                                            <button type="button"
                                                    onclick="event.stopPropagation(); playAudioUrl('{{ $currentEntry->audio_url }}')"
                                                    title="Nghe lại phát âm từ gốc"
                                                    class="rounded-full p-1 text-emerald-600 hover:bg-emerald-50 border border-transparent hover:border-emerald-100 transition cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                                </svg>
                                            </button>
                                        @else
                                            <span class="w-6"></span>
                                        @endif
                                    </div>

                                    <div class="my-auto py-1 space-y-1 w-full">
                                        @if ($hasMeaningText)
                                            <div class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight" id="cardBackText">
                                                {{ $backRaw }}
                                            </div>
                                        @else
                                            <div class="space-y-0.5" id="cardBackTextContainer">
                                                <div class="text-lg font-bold text-slate-700 tracking-tight" id="cardBackText">
                                                    Chưa có mô tả ý nghĩa
                                                </div>
                                                <p class="text-2xs text-slate-400 italic">
                                                    Từ vựng này chưa được nhập nội dung giải nghĩa.
                                                </p>
                                            </div>
                                        @endif

                                        @if (!empty($practiceQuestion['example_sentence']))
                                            <div class="rounded-lg bg-slate-50 p-2 border border-slate-100 text-2xs text-slate-600 text-left space-y-0.5 max-w-sm mx-auto">
                                                <p class="font-medium text-slate-800 italic leading-snug">"{{ $practiceQuestion['example_sentence'] }}"</p>
                                                @if (!empty($practiceQuestion['example_translation']))
                                                    <p class="not-italic text-slate-500 text-2xs leading-snug">({{ $practiceQuestion['example_translation'] }})</p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div class="inline-flex items-center justify-center space-x-1 text-2xs font-semibold text-emerald-700 bg-emerald-50/70 py-1 px-3 rounded-full border border-emerald-100 shadow-2xs self-center">
                                        <span>↺ Bấm thẻ để lật lại mặt trước</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                <!-- 2. Dạng Điền từ vào câu (Fill in the blank) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'fill_in_the_blank')
                    @php
                        $clozeFormat = $practiceQuestion['cloze_format'] ?? 'input';
                    @endphp
                    <div class="space-y-3">
                        <div class="rounded-xl bg-slate-50 p-3.5 sm:p-4 text-center border border-slate-200">
                            <p class="text-base sm:text-lg font-medium text-slate-900 leading-relaxed italic" id="clozeDisplaySentence">
                                "{{ $practiceQuestion['cloze_sentence'] ?? '[ ______ ]' }}"
                            </p>
                            {{-- Bản dịch câu: ẩn ban đầu, chỉ hiển thị sau khi nộp đáp án --}}
                            <div id="clozeTranslationBox" class="mt-3 pt-3 border-t border-slate-200/80 hidden text-center">
                                <span class="text-2xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Toàn bộ nghĩa của câu:</span>
                                <div id="clozeTranslation" class="text-xs sm:text-sm font-semibold text-slate-800 italic bg-white p-2.5 rounded-xl border border-slate-200"></div>
                            </div>
                        </div>

                        @if ($clozeFormat === 'choice' && !empty($practiceQuestion['options']))
                            <div class="space-y-1.5">
                                <label class="block text-2xs font-bold uppercase tracking-wider text-slate-600 text-center">
                                    Chọn từ đúng để điền vào chỗ trống:
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5 max-w-lg mx-auto">
                                    @foreach ($practiceQuestion['options'] as $idx => $opt)
                                        @php
                                            $letter = chr(65 + $idx);
                                        @endphp
                                        <button type="button"
                                                onclick="selectPracticeOption(this)"
                                                data-option-value="{{ $opt['text'] }}"
                                                data-is-correct="{{ !empty($opt['is_correct']) ? 'true' : 'false' }}"
                                                class="practice-option w-full text-left rounded-xl border-2 border-slate-200 bg-white p-3 text-xs sm:text-sm font-semibold text-slate-800 transition hover:border-indigo-400 cursor-pointer flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-slate-100 text-2xs font-bold text-slate-600 shrink-0">
                                                    {{ $letter }}
                                                </span>
                                                <span class="option-text font-medium">{{ $opt['text'] }}</span>
                                            </div>
                                            <span class="result-badge hidden"></span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="space-y-1 max-w-md mx-auto">
                                <label for="practiceInputText" class="block text-2xs font-bold uppercase tracking-wider text-slate-600">
                                    Nhập từ vựng cần điền:
                                </label>
                                <input type="text"
                                       id="practiceInputText"
                                       autocomplete="off"
                                       autocorrect="off"
                                       spellcheck="false"
                                       autofocus
                                       placeholder="Gõ từ vào đây..."
                                       oninput="enablePracticeSubmit()"
                                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 sm:py-2.5 text-sm sm:text-base font-semibold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        @endif
                    </div>

                <!-- 3. Dạng Ghép cặp (Matching Pairs) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'matching_pairs')
                    @php
                        $matchingTerms = $practiceQuestion['shuffled_terms'] ?? $practiceQuestion['pairs'] ?? [];
                        $matchingMeanings = $practiceQuestion['shuffled_meanings'] ?? $practiceQuestion['pairs'] ?? [];
                        // Dự phòng xáo trộn lệch hàng nếu dữ liệu cũ chưa có shuffled_meanings riêng
                        if (count($matchingTerms) > 1 && $matchingTerms === $matchingMeanings) {
                            $matchingMeanings = [];
                            $n = count($matchingTerms);
                            for ($i = 0; $i < $n; $i++) {
                                $matchingMeanings[] = $matchingTerms[($i + 1) % $n];
                            }
                        }
                    @endphp
                    <div class="space-y-2">
                        <p class="text-xs text-slate-500">Bấm một từ ở cột trái rồi bấm nghĩa tương ứng ở cột phải:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5">
                            <!-- Cột từ -->
                            <div class="space-y-1.5">
                                <div class="text-2xs font-bold text-slate-500 uppercase">Cột Từ vựng</div>
                                @foreach ($matchingTerms as $p)
                                    <button type="button"
                                            onclick="selectPairTerm('{{ $p['id'] }}', this)"
                                            id="pairTerm_{{ $p['id'] }}"
                                            class="pair-term-btn w-full text-left rounded-xl border-2 border-slate-200 bg-white px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-bold text-slate-800 transition hover:border-indigo-400 cursor-pointer min-h-[38px] sm:min-h-[42px] flex items-center">
                                        <span class="truncate">{{ $p['term'] ?? ($p['text'] ?? '') }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <!-- Cột nghĩa -->
                            <div class="space-y-1.5">
                                <div class="text-2xs font-bold text-slate-500 uppercase">Cột Ý nghĩa</div>
                                @foreach ($matchingMeanings as $p)
                                    <button type="button"
                                            onclick="selectPairMeaning('{{ $p['id'] }}', this)"
                                            id="pairMeaning_{{ $p['id'] }}"
                                            class="pair-meaning-btn w-full text-left rounded-xl border-2 border-slate-200 bg-white px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-slate-800 transition hover:border-indigo-400 cursor-pointer min-h-[38px] sm:min-h-[42px] flex items-center">
                                        <span class="truncate">{{ $p['meaning'] ?? ($p['text'] ?? '') }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                <!-- 4. Dạng Nghe và Viết (Listen and Type) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'listen_and_type')
                    <div class="space-y-3 text-center">
                        <div class="py-1">
                            <button type="button"
                                    onclick="playAudioOrSpeak('{{ $practiceQuestion['audio_url'] ?? $currentEntry?->audio_url ?? '' }}', '{{ addslashes($currentEntry->term) }}', '{{ $deck->learningLanguage->code ?? 'en' }}')"
                                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-indigo-500 transition cursor-pointer">
                                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                </svg>
                                🔊 Nghe phát âm từ vựng
                            </button>
                            <p class="text-2xs text-slate-400 mt-1">Bấm nút trên để nghe và gõ lại đúng từ vựng</p>
                        </div>

                        <div class="space-y-1 text-left max-w-md mx-auto">
                            <label for="practiceInputText" class="block text-2xs font-bold uppercase tracking-wider text-slate-600">
                                Nhập từ bạn vừa nghe được:
                            </label>
                            <input type="text"
                                   id="practiceInputText"
                                   autocomplete="off"
                                   autocorrect="off"
                                   autocapitalize="off"
                                   spellcheck="false"
                                   autofocus
                                   placeholder="Gõ từ bạn nghe được..."
                                   oninput="enablePracticeSubmit()"
                                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 sm:py-2.5 text-sm sm:text-base font-semibold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>

                <!-- 5. Dạng Nghe và Chọn (Listen and Choose) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'listen_and_choose')
                    <div class="space-y-3 text-center">
                        <div class="py-1">
                            <button type="button"
                                    onclick="playAudioOrSpeak('{{ $practiceQuestion['audio_url'] ?? $currentEntry?->audio_url ?? '' }}', '{{ addslashes($currentEntry->term) }}', '{{ $deck->learningLanguage->code ?? 'en' }}')"
                                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-indigo-500 transition cursor-pointer">
                                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                </svg>
                                🔊 Nghe phát âm từ vựng
                            </button>
                            <p class="text-2xs text-slate-400 mt-1">Nghe phát âm và chọn nghĩa chính xác bên dưới</p>
                        </div>

                        <!-- Lưới 4 phương án dạng 2x2 trên tablet/desktop, 1 cột gọn trên điện thoại -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5" id="practiceOptionsContainer">
                            @foreach ($practiceQuestion['options'] ?? [] as $idx => $opt)
                                <button type="button"
                                        data-option-id="{{ $opt['id'] }}"
                                        data-is-correct="{{ $opt['is_correct'] ? 'true' : 'false' }}"
                                        onclick="selectPracticeOption(this)"
                                        class="practice-option w-full text-left rounded-xl border-2 border-slate-200 bg-white p-2.5 sm:p-3 text-xs sm:text-sm font-semibold text-slate-800 transition hover:border-indigo-300 hover:bg-slate-50/50 focus:outline-none cursor-pointer flex items-center justify-between min-h-[44px]">
                                    <div class="flex items-center space-x-2.5 min-w-0">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-600">
                                            {{ chr(65 + $idx) }}
                                        </span>
                                        <span class="truncate">{{ $opt['text'] }}</span>
                                    </div>
                                    <span class="result-badge hidden text-2xs font-bold px-1.5 py-0.5 rounded-md shrink-0"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                <!-- 6. Dạng Sắp xếp chữ cái / mảnh ghép (Word Unscramble) -->
                @elseif (($practiceQuestion['type'] ?? '') === 'word_unscramble')
                    <div class="space-y-2.5">
                        <div class="text-center py-1">
                            <span class="text-2xs uppercase font-bold text-slate-500">Ý nghĩa cần ghép từ:</span>
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 mt-0.5">
                                {{ $practiceQuestion['question_text'] ?? $currentMeaning->meaning }}
                            </h3>
                        </div>

                        <!-- Vùng Đáp án (Answer Tray) -->
                        <div class="rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/30 p-2 min-h-[48px] sm:min-h-[52px] flex flex-wrap items-center justify-center gap-1.5" id="unscrambleAnswerTray">
                            <span class="text-xs text-indigo-400 font-medium" id="trayPlaceholder">Bấm vào các ký tự bên dưới để ghép từ</span>
                        </div>

                        <!-- Vùng Mảnh ký tự (Tile Tray) -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-2xs font-bold uppercase tracking-wider text-slate-400">Các mảnh ký tự:</span>
                                <button type="button" onclick="resetUnscramble()" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer">
                                    ↺ Làm lại
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-1.5" id="unscrambleTileTray">
                                @foreach ($practiceQuestion['tiles'] ?? [] as $tile)
                                    <button type="button"
                                            id="{{ $tile['id'] }}"
                                            data-letter="{{ $tile['letter'] }}"
                                            onclick="moveTileToAnswer(this)"
                                            class="unscramble-tile h-9 w-9 sm:h-10 sm:w-10 rounded-xl border-2 border-slate-300 bg-white text-base font-bold text-slate-800 shadow-2xs hover:border-indigo-500 hover:bg-indigo-50 transition flex items-center justify-center cursor-pointer">
                                        {{ $tile['letter'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                <!-- 7 & 8. Dạng Trắc nghiệm chọn nghĩa hoặc chọn từ -->
                @else
                    <div class="text-center py-1">
                        <span class="text-2xs font-bold uppercase tracking-wider text-slate-400">
                            {{ ($practiceQuestion['type'] ?? '') === 'multiple_choice_meaning_to_term' ? 'Nhìn nghĩa chọn từ:' : 'Nhìn từ chọn nghĩa:' }}
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-indigo-950 mt-0.5 font-serif"
                            dir="{{ $deck->learningLanguage->direction }}"
                            lang="{{ $deck->learningLanguage->code }}">
                            {{ $practiceQuestion['question_text'] ?? ($practiceQuestion['term'] ?? $currentEntry->term) }}
                        </h2>
                        @if (!empty($practiceQuestion['pronunciation']) && ($practiceQuestion['type'] ?? '') !== 'multiple_choice_meaning_to_term')
                            <div class="text-xs font-mono text-slate-500 mt-0.5">
                                /{{ $practiceQuestion['pronunciation'] }}/
                            </div>
                        @endif
                        @if (!empty($practiceQuestion['part_of_speech']))
                            <div class="mt-1">
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-2xs font-semibold text-purple-700">
                                    {{ $practiceQuestion['part_of_speech'] }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Lưới 4 phương án dạng 2x2 trên tablet/desktop, 1 cột gọn trên điện thoại -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5" id="practiceOptionsContainer">
                        @foreach ($practiceQuestion['options'] ?? [] as $idx => $opt)
                            <button type="button"
                                    data-option-id="{{ $opt['id'] }}"
                                    data-is-correct="{{ $opt['is_correct'] ? 'true' : 'false' }}"
                                    onclick="selectPracticeOption(this)"
                                    class="practice-option w-full text-left rounded-xl border-2 border-slate-200 bg-white p-2.5 sm:p-3 text-xs sm:text-sm font-semibold text-slate-800 transition hover:border-indigo-300 hover:bg-slate-50/50 focus:outline-none cursor-pointer flex items-center justify-between min-h-[44px]">
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-600">
                                        {{ chr(65 + $idx) }}
                                    </span>
                                    <span class="truncate">{{ $opt['text'] }}</span>
                                </div>
                                <span class="result-badge hidden text-2xs font-bold px-1.5 py-0.5 rounded-md shrink-0"></span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Khối phản hồi đánh giá khách quan -->
                <div id="evaluationFeedback" class="hidden rounded-xl p-3 text-xs sm:text-sm font-semibold space-y-1"></div>
            </div>
        @endif
    </div>

    <!-- Thanh thao tác dưới (Action Bar - Gọn gàng ngay dưới câu hỏi, không bắt cuộn) -->
    <div class="rounded-2xl border border-slate-200 bg-white/95 p-2.5 sm:p-3 shadow-2xs backdrop-blur-md">
        <div class="flex items-center justify-between gap-2">
            @if ($session->mode === \App\Enums\StudyMode::LearnNew)
                <!-- ================= THAO TÁC LÀM QUEN ================= -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    @if ($previousItem)
                        <a href="{{ route('study.session.show', ['session' => $session, 'item_id' => $previousItem->id]) }}"
                           class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                            &larr; <span class="hidden sm:inline ml-1">Xem lại</span>
                        </a>
                    @endif

                    <button type="button"
                            id="difficultBtn"
                            onclick="toggleDifficult()"
                            title="Đánh dấu nghĩa này là khó để củng cố kỹ hơn"
                            class="inline-flex items-center rounded-xl border {{ $currentItem->is_difficult ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-slate-300 bg-white text-slate-700' }} px-2.5 py-1.5 text-xs font-semibold shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                        <span id="difficultIcon" class="mr-1 {{ $currentItem->is_difficult ? 'text-amber-500 font-bold' : 'text-slate-400' }}">⭐</span>
                        <span id="difficultText">{{ $currentItem->is_difficult ? 'Đã khó' : 'Khó' }}</span>
                    </button>

                    <button type="button"
                            id="selfKnownBtn"
                            onclick="submitSelfKnown()"
                            title="Tự khai báo đã biết từ này (xếp lịch ôn sau 2 ngày)"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 transition cursor-pointer">
                        <span class="mr-1 text-emerald-600">✓</span>
                        Tôi đã biết
                    </button>
                </div>

                <div>
                    <button type="button"
                            id="continueFamiliarizeBtn"
                            onclick="submitFamiliarization()"
                            class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-indigo-600 px-5 py-2 text-xs sm:text-sm font-bold text-white shadow-2xs hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/20 cursor-pointer">
                        <span id="submitBtnSpinner" class="hidden mr-1.5">
                            <svg class="h-3.5 w-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </span>
                        <span id="continueBtnText">Tiếp tục &rarr;</span>
                    </button>
                </div>

            @else
                <!-- ================= THAO TÁC LUYỆN TẬP / ÔN TẬP ================= -->
                @if (($practiceQuestion['type'] ?? '') === 'flashcard')
                    <!-- Thanh thao tác riêng cho Flashcard -->
                    <div id="flashcardUnflippedActions" class="w-full flex items-center justify-between gap-2">
                        <button type="button"
                                onclick="toggleFlashcardFlip()"
                                class="w-full inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-bold text-white shadow-2xs hover:bg-indigo-500 transition cursor-pointer">
                            🔄 Lật thẻ xem đáp án (Phím Cách)
                        </button>
                    </div>

                    <div id="flashcardActions" class="hidden w-full flex flex-col items-center gap-1.5">
                        <div class="w-full flex items-center justify-between px-1">
                            <p class="text-2xs text-slate-600 font-medium">Bạn có nhớ đáp án trước khi lật thẻ không?</p>
                            <button type="button"
                                    onclick="toggleFlashcardFlip()"
                                    class="text-2xs text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer">
                                ↺ Lật lại mặt trước
                            </button>
                        </div>
                        <div class="w-full flex items-center justify-between gap-2">
                            <button type="button"
                                    onclick="submitSelfAssessment('self_forgot')"
                                    class="flex-1 inline-flex items-center justify-center rounded-xl border-2 border-rose-300 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-800 hover:bg-rose-100 transition cursor-pointer">
                                ✗ Chưa nhớ
                            </button>
                            <button type="button"
                                    onclick="submitSelfAssessment('self_remember')"
                                    class="flex-1 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 shadow-2xs transition cursor-pointer">
                                ✓ Đã nhớ
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex items-center space-x-2">
                        <button type="button"
                                onclick="skipPractice()"
                                id="skipBtn"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                            Bỏ qua
                        </button>

                        <button type="button"
                                onclick="requestHint()"
                                id="hintBtn"
                                class="inline-flex items-center rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition cursor-pointer">
                            💡 Gợi ý
                        </button>
                    </div>

                    <div>
                        <button type="button"
                                id="submitPracticeBtn"
                                onclick="handlePracticeButtonClick()"
                                disabled
                                class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-slate-300 px-5 py-2 text-xs sm:text-sm font-bold text-white shadow-2xs transition focus:outline-none cursor-not-allowed">
                            <span id="practiceBtnSpinner" class="hidden mr-1.5">
                                <svg class="h-3.5 w-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
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

<!-- Modal hướng dẫn phím tắt -->
<div id="shortcutsModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900">Phím tắt thao tác nhanh</h3>
        <div class="text-xs text-slate-600 space-y-2">
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">1, 2, 3, 4</span>
                <span>Chọn phương án A, B, C, D</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">Space (Cách)</span>
                <span>Lật thẻ / Tiếp tục</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">Enter</span>
                <span>Kiểm tra / Sang câu tiếp</span>
            </div>
        </div>
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
    const isLearnNewMode = {{ $session->mode === \App\Enums\StudyMode::LearnNew ? 'true' : 'false' }};
    const exerciseType = '{{ $practiceQuestion['type'] ?? '' }}';
    const clozeFormat = '{{ $practiceQuestion['cloze_format'] ?? 'input' }}';

    let isDifficult = {{ $currentItem->is_difficult ? 'true' : 'false' }};
    let usedHint = false;
    let isSubmitting = false;
    let isEvaluated = false;
    let selectedOptionElement = null;
    let countdownTimer = null;

    // Quản lý toggle Tự chuyển khi đúng
    let autoAdvance = localStorage.getItem('study_auto_advance') === 'true';
    const autoAdvanceToggle = document.getElementById('autoAdvanceToggle');
    if (autoAdvanceToggle) {
        autoAdvanceToggle.checked = autoAdvance;
    }

    function toggleAutoAdvanceSetting(val) {
        autoAdvance = val;
        localStorage.setItem('study_auto_advance', val ? 'true' : 'false');
    }

    // Ẩn / Hiện dịch ví dụ
    function toggleTranslations() {
        document.querySelectorAll('.example-translation').forEach(el => {
            el.classList.toggle('hidden');
        });
    }

    // ================= FLASHCARD LOGIC =================
    let isReversedFlashcard = false;
    let isFlashcardFlipped = false;
    const originalFrontText = '{{ addslashes($practiceQuestion['front_text'] ?? ($currentEntry->term ?: 'Chưa có từ vựng')) }}';
    const originalBackText = '{{ addslashes(!empty(trim($backRaw ?? '')) ? $backRaw : ($currentEntry->note ?: 'Chưa có mô tả ý nghĩa')) }}';

    function toggleFlashcardDirection(e) {
        if (e) e.stopPropagation();
        if (isEvaluated) return;
        isReversedFlashcard = !isReversedFlashcard;
        const frontTextEl = document.getElementById('cardFrontText');
        const backTextEl = document.getElementById('cardBackText');
        const frontLabel = document.getElementById('cardFrontSideLabel');
        const backLabel = document.getElementById('cardBackSideLabel');
        const directionLabel = document.getElementById('directionLabel');

        if (isReversedFlashcard) {
            frontTextEl.innerText = originalBackText;
            backTextEl.innerText = originalFrontText;
            if (frontLabel) frontLabel.innerText = 'Mặt trước (Nghĩa)';
            if (backLabel) backLabel.innerText = 'Mặt sau (Từ vựng)';
            if (directionLabel) directionLabel.innerText = 'Nghĩa → Từ';
        } else {
            frontTextEl.innerText = originalFrontText;
            backTextEl.innerText = originalBackText;
            if (frontLabel) frontLabel.innerText = 'Mặt trước';
            if (backLabel) backLabel.innerText = 'Mặt sau (Giải nghĩa)';
            if (directionLabel) directionLabel.innerText = 'Từ → Nghĩa';
        }
    }

    function toggleFlashcardFlip() {
        const cardEl = document.getElementById('flashcardElement');
        if (!cardEl) return;

        isFlashcardFlipped = !isFlashcardFlipped;
        if (isFlashcardFlipped) {
            cardEl.classList.remove('flip-to-front');
            void cardEl.offsetWidth; // Force reflow để kích hoạt hoạt ảnh lật 3D
            cardEl.classList.add('flip-to-back', 'is-flipped');
            document.getElementById('flashcardUnflippedActions')?.classList.add('hidden');
            document.getElementById('flashcardActions')?.classList.remove('hidden');
        } else {
            cardEl.classList.remove('flip-to-back');
            void cardEl.offsetWidth; // Force reflow để kích hoạt hoạt ảnh lật 3D
            cardEl.classList.add('flip-to-front');
            cardEl.classList.remove('is-flipped');
            document.getElementById('flashcardUnflippedActions')?.classList.remove('hidden');
            document.getElementById('flashcardActions')?.classList.add('hidden');
        }
    }

    function flipFlashcard() {
        toggleFlashcardFlip();
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
            window.location.href = '{{ route('study.session.show', $session) }}';
        })
        .catch(err => {
            console.error(err);
            updateSaveIndicator('Lưu thất bại');
            showNetworkError();
            setSubmittingState(false);
        });
    }

    // ================= MATCHING PAIRS =================
    let selectedTermId = null;
    let selectedMeaningId = null;
    let matchedPairsCount = 0;
    const totalMatchingPairs = {{ !empty($practiceQuestion['pairs']) ? count($practiceQuestion['pairs']) : 0 }};

    function selectPairTerm(pairId, btn) {
        if (btn.disabled || isEvaluated) return;

        // Nếu bấm lại vào chính ô từ vựng đang chọn -> Bỏ chọn
        if (selectedTermId === pairId) {
            btn.classList.remove('border-indigo-600', 'bg-indigo-50');
            selectedTermId = null;
            return;
        }

        // Nếu trước đó đã chọn một ô bên cột Ý nghĩa -> Tiến hành ghép cặp
        if (selectedMeaningId) {
            checkPairMatch(pairId, selectedMeaningId);
            return;
        }

        // Đổi lựa chọn từ vựng
        document.querySelectorAll('.pair-term-btn').forEach(b => {
            if (!b.disabled) b.classList.remove('border-indigo-600', 'bg-indigo-50');
        });
        btn.classList.add('border-indigo-600', 'bg-indigo-50');
        selectedTermId = pairId;
    }

    function selectPairMeaning(pairId, btn) {
        if (btn.disabled || isEvaluated) return;

        // Nếu bấm lại vào chính ô ý nghĩa đang chọn -> Bỏ chọn
        if (selectedMeaningId === pairId) {
            btn.classList.remove('border-indigo-600', 'bg-indigo-50');
            selectedMeaningId = null;
            return;
        }

        // Nếu trước đó đã chọn một ô bên cột Từ vựng -> Tiến hành ghép cặp
        if (selectedTermId) {
            checkPairMatch(selectedTermId, pairId);
            return;
        }

        // Đổi lựa chọn ý nghĩa
        document.querySelectorAll('.pair-meaning-btn').forEach(b => {
            if (!b.disabled) b.classList.remove('border-indigo-600', 'bg-indigo-50');
        });
        btn.classList.add('border-indigo-600', 'bg-indigo-50');
        selectedMeaningId = pairId;
    }

    function checkPairMatch(termId, meaningId) {
        const termBtn = document.getElementById(`pairTerm_${termId}`);
        const meaningBtn = document.getElementById(`pairMeaning_${meaningId}`);

        fetch(`/study/sessions/${sessionId}/check-pair`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ term_id: termId, meaning_id: meaningId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.is_correct) {
                const colorIdx = matchedPairsCount % 6;
                const pairNumber = matchedPairsCount + 1;

                const createBadge = () => {
                    const badge = document.createElement('span');
                    badge.className = `matched-pair-badge matched-pair-badge-${colorIdx}`;
                    badge.innerHTML = `<span class="inline-block w-1.5 h-1.5 rounded-full bg-current"></span><span>✓ Cặp ${pairNumber}</span>`;
                    return badge;
                };

                if (termBtn) {
                    termBtn.disabled = true;
                    termBtn.className = `pair-term-btn w-full text-left rounded-xl border-2 px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-bold cursor-not-allowed min-h-[38px] sm:min-h-[42px] flex items-center justify-between matched-pair-${colorIdx}`;
                    termBtn.appendChild(createBadge());
                }
                if (meaningBtn) {
                    meaningBtn.disabled = true;
                    meaningBtn.className = `pair-meaning-btn w-full text-left rounded-xl border-2 px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold cursor-not-allowed min-h-[38px] sm:min-h-[42px] flex items-center justify-between matched-pair-${colorIdx}`;
                    meaningBtn.appendChild(createBadge());
                }

                matchedPairsCount++;
                selectedTermId = null;
                selectedMeaningId = null;

                if (matchedPairsCount >= totalMatchingPairs) {
                    enablePracticeSubmit();
                    submitPracticeDirect('pass');
                }
            } else {
                if (termBtn) {
                    termBtn.classList.remove('border-indigo-600', 'bg-indigo-50');
                    termBtn.classList.add('border-rose-400', 'bg-rose-50');
                    setTimeout(() => {
                        termBtn.classList.remove('border-rose-400', 'bg-rose-50');
                    }, 600);
                }
                if (meaningBtn) {
                    meaningBtn.classList.remove('border-indigo-600', 'bg-indigo-50');
                    meaningBtn.classList.add('border-rose-400', 'bg-rose-50');
                    setTimeout(() => {
                        meaningBtn.classList.remove('border-rose-400', 'bg-rose-50');
                    }, 600);
                }
                selectedTermId = null;
                selectedMeaningId = null;
            }
        })
        .catch(err => {
            console.error(err);
            if (termBtn) termBtn.classList.remove('border-indigo-600', 'bg-indigo-50');
            if (meaningBtn) meaningBtn.classList.remove('border-indigo-600', 'bg-indigo-50');
            selectedTermId = null;
            selectedMeaningId = null;
        });
    }

    // ================= WORD UNSCRAMBLE =================
    let unscrambleAnswerTiles = [];
    function moveTileToAnswer(btn) {
        if (isEvaluated) return;
        const char = btn.getAttribute('data-letter');
        const id = btn.id;

        btn.classList.add('hidden');
        unscrambleAnswerTiles.push({ id: id, letter: char });
        renderUnscrambleAnswer();
        enablePracticeSubmit();
    }

    function removeTileFromAnswer(index) {
        if (isEvaluated) return;
        if (index < 0 || index >= unscrambleAnswerTiles.length) return;
        const removed = unscrambleAnswerTiles.splice(index, 1)[0];
        const tileBtn = document.getElementById(removed.id);
        if (tileBtn) tileBtn.classList.remove('hidden');
        renderUnscrambleAnswer();
    }

    function renderUnscrambleAnswer() {
        const tray = document.getElementById('unscrambleAnswerTray');
        if (!tray) return;

        if (unscrambleAnswerTiles.length === 0) {
            tray.innerHTML = '<span class="text-xs text-indigo-400 font-medium" id="trayPlaceholder">Bấm vào các ký tự bên dưới để ghép từ</span>';
            return;
        }

        tray.innerHTML = '';
        unscrambleAnswerTiles.forEach((t, idx) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'h-9 w-9 sm:h-10 sm:w-10 rounded-xl bg-indigo-600 text-white font-bold text-sm sm:text-base shadow-2xs hover:bg-rose-600 transition flex items-center justify-center cursor-pointer';
            b.innerText = t.letter;
            b.onclick = () => removeTileFromAnswer(idx);
            tray.appendChild(b);
        });
    }

    function resetUnscramble() {
        if (isEvaluated) return;
        unscrambleAnswerTiles = [];
        document.querySelectorAll('.unscramble-tile').forEach(b => b.classList.remove('hidden'));
        renderUnscrambleAnswer();
    }

    // ================= MULTIPLE CHOICE & INPUT =================
    function enablePracticeSubmit() {
        const submitBtn = document.getElementById('submitPracticeBtn');
        const input = document.getElementById('practiceInputText');
        if (submitBtn && !isEvaluated) {
            if (input && input.value.trim() === '') {
                submitBtn.disabled = true;
                submitBtn.classList.add('bg-slate-300', 'cursor-not-allowed');
                submitBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700', 'cursor-pointer');
            } else {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-slate-300', 'cursor-not-allowed');
                submitBtn.classList.add('bg-purple-600', 'hover:bg-purple-700', 'cursor-pointer');
            }
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

    function handlePracticeButtonClick() {
        if (isEvaluated) {
            cancelAutoAdvance();
            window.location.href = '{{ route('study.session.show', $session) }}';
            return;
        }

        let isCorrect = false;
        let userAnswer = null;

        if (exerciseType === 'fill_in_the_blank') {
            if (clozeFormat === 'choice') {
                userAnswer = selectedOptionElement?.getAttribute('data-option-value') || '';
            } else {
                userAnswer = document.getElementById('practiceInputText')?.value.trim() || '';
            }
            submitPracticeDirect(null, userAnswer);
            return;
        } else if (exerciseType === 'listen_and_type') {
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

    function submitPracticeDirect(resultType, userAnswer = null) {
        isEvaluated = true;
        setSubmittingState(true);
        updateSaveIndicator('Đang lưu...');

        // Khóa các lựa chọn
        document.querySelectorAll('.practice-option').forEach(btn => {
            btn.classList.add('pointer-events-none');
            const isOptCorrect = btn.getAttribute('data-is-correct') === 'true';
            const badge = btn.querySelector('.result-badge');

            if (resultType !== null) {
                if (isOptCorrect) {
                    btn.classList.add('border-emerald-500', 'bg-emerald-50/70', 'text-emerald-900');
                    if (badge) {
                        badge.innerText = '✓ Đúng';
                        badge.className = 'result-badge text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-200 text-emerald-800';
                        badge.classList.remove('hidden');
                    }
                } else if (btn === selectedOptionElement && !isOptCorrect) {
                    btn.classList.add('border-rose-500', 'bg-rose-50/70', 'text-rose-900');
                    if (badge) {
                        badge.innerText = '✗ Sai';
                        badge.className = 'result-badge text-xs font-bold px-2 py-0.5 rounded-md bg-rose-200 text-rose-800';
                        badge.classList.remove('hidden');
                    }
                }
            }
        });

        const practiceInput = document.getElementById('practiceInputText');
        if (practiceInput) {
            practiceInput.disabled = true;
            if (resultType === 'pass') {
                practiceInput.classList.add('border-emerald-500', 'bg-emerald-50');
            } else if (resultType === 'fail') {
                practiceInput.classList.add('border-rose-500', 'bg-rose-50');
            }
        }

        const payload = { used_hint: usedHint };
        if (userAnswer !== null) {
            payload.user_answer = userAnswer;
        } else {
            payload.result = resultType;
        }

        fetch(`/study/sessions/${sessionId}/items/${currentItemId}/practice`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            hideNetworkError();
            setSubmittingState(false);
            updateSaveIndicator('Đã lưu');

            const grading = data.grading;
            const isPass = grading ? grading.is_correct : (resultType === 'pass');

            // Cập nhật giao diện trắc nghiệm nếu được chấm từ server (fill_in_the_blank dạng choice)
            if (grading) {
                if (practiceInput) {
                    practiceInput.classList.remove('border-emerald-500', 'bg-emerald-50', 'border-rose-500', 'bg-rose-50');
                    practiceInput.classList.add(isPass ? 'border-emerald-500' : 'border-rose-500', isPass ? 'bg-emerald-50' : 'bg-rose-50');
                }

                document.querySelectorAll('.practice-option').forEach(btn => {
                    const optVal = (btn.getAttribute('data-option-value') || '').trim().toLowerCase();
                    const correctVal = (grading.correct_answer || '').trim().toLowerCase();
                    const badge = btn.querySelector('.result-badge');

                    if (optVal === correctVal) {
                        btn.classList.add('border-emerald-500', 'bg-emerald-50/70', 'text-emerald-900');
                        if (badge) {
                            badge.innerText = '✓ Đúng';
                            badge.className = 'result-badge text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-200 text-emerald-800';
                            badge.classList.remove('hidden');
                        }
                    } else if (btn === selectedOptionElement && !isPass) {
                        btn.classList.add('border-rose-500', 'bg-rose-50/70', 'text-rose-900');
                        if (badge) {
                            badge.innerText = '✗ Sai';
                            badge.className = 'result-badge text-xs font-bold px-2 py-0.5 rounded-md bg-rose-200 text-rose-800';
                            badge.classList.remove('hidden');
                        }
                    }
                });
            }

            const feedback = document.getElementById('evaluationFeedback');
            if (feedback) {
                const answerText = grading?.correct_answer || '{{ addslashes($currentEntry->term) }}';
                if (isPass) {
                    feedback.className = 'rounded-2xl p-4 text-sm font-semibold bg-emerald-50 border border-emerald-200 text-emerald-900';
                    feedback.innerHTML = '<div>🎉 Chính xác! Bạn đã ghi nhớ rất tốt từ này.</div>';
                } else {
                    feedback.className = 'rounded-2xl p-4 text-sm font-semibold bg-rose-50 border border-rose-200 text-rose-900';
                    feedback.innerHTML = `<div>✗ Chưa chính xác. Đáp án đúng là: <strong class="text-slate-900 text-base">${answerText}</strong></div>`;
                }

                const transText = grading?.example_translation || '{{ addslashes($practiceQuestion['example_translation'] ?? '') }}';
                if (transText) {
                    const cleanTrans = transText.replace(/\[\s*_{3,}\s*\]/g, '').replace(/\s+/g, ' ').trim();
                    if (cleanTrans) {
                        feedback.innerHTML += `<div class="text-xs sm:text-sm font-normal text-slate-800 mt-2 border-t border-slate-200/60 pt-2"><strong class="font-semibold text-slate-900">Dịch nghĩa câu:</strong> "${cleanTrans}"</div>`;
                    }
                }

                const explText = grading?.explanation || '{{ addslashes($practiceQuestion['explanation'] ?? '') }}';
                if (explText) {
                    const borderClass = transText ? 'mt-1' : 'mt-2 border-t border-slate-200/60 pt-2';
                    feedback.innerHTML += `<div class="text-xs font-normal text-slate-600 ${borderClass}"><strong class="font-semibold text-slate-800">Giải thích:</strong> ${explText}</div>`;
                }

                feedback.classList.remove('hidden');
            }

            // Cập nhật câu hoàn chỉnh và bản dịch
            const clozeSentence = document.getElementById('clozeDisplaySentence');
            if (clozeSentence) {
                const fullText = grading?.sentence_full || '{{ addslashes($practiceQuestion["sentence_full"] ?? $practiceQuestion["example_sentence"] ?? "") }}';
                const answerWord = grading?.correct_answer || '{{ addslashes($practiceQuestion["blank_answer"] ?? $practiceQuestion["correct_answer"] ?? "") }}';

                if (fullText && answerWord) {
                    const regex = new RegExp('(' + answerWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                    const highlighted = fullText.replace(regex, '<span class="bg-amber-200 text-amber-950 font-bold px-1.5 py-0.5 rounded-md border border-amber-300 shadow-2xs">$1</span>');
                    clozeSentence.innerHTML = `"${highlighted}"`;
                } else if (fullText) {
                    clozeSentence.innerText = `"${fullText}"`;
                }
            }

            const clozeTranslationBox = document.getElementById('clozeTranslationBox');
            const clozeTranslation = document.getElementById('clozeTranslation');
            if (clozeTranslation) {
                const transText = grading?.example_translation || '{{ addslashes($practiceQuestion['example_translation'] ?? '') }}';
                if (transText) {
                    const cleanTrans = transText.replace(/\[\s*_{3,}\s*\]/g, '').replace(/\s+/g, ' ').trim();
                    clozeTranslation.innerText = `"${cleanTrans}"`;
                    if (clozeTranslationBox) {
                        clozeTranslationBox.classList.remove('hidden');
                    }
                    clozeTranslation.classList.remove('hidden');
                }
            }

            // Đổi nút Kiểm tra thành nút Tiếp tục câu tiếp
            const submitBtn = document.getElementById('submitPracticeBtn');
            const submitBtnText = document.getElementById('submitPracticeBtnText');
            if (submitBtn && submitBtnText) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-slate-300', 'bg-purple-600', 'cursor-not-allowed');
                submitBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-500', 'cursor-pointer');
                submitBtnText.innerText = 'Tiếp tục câu tiếp →';
            }

            // Xử lý tự chuyển câu khi đúng (hiển thị trực tiếp trên nút Tiếp tục, không tạo thêm khung)
            if (isPass && autoAdvance) {
                let timeLeft = 1.2;
                const interval = 100;
                countdownTimer = setInterval(() => {
                    timeLeft -= 0.1;
                    if (submitBtnText) {
                        submitBtnText.innerText = `Tiếp tục (${Math.max(0, timeLeft).toFixed(1)}s) →`;
                    }
                    if (timeLeft <= 0) {
                        clearInterval(countdownTimer);
                        countdownTimer = null;
                        window.location.href = '{{ route('study.session.show', $session) }}';
                    }
                }, interval);
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

    function cancelAutoAdvance() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
            const submitBtnText = document.getElementById('submitPracticeBtnText');
            if (submitBtnText) {
                submitBtnText.innerText = 'Tiếp tục câu tiếp →';
            }
        }
    }

    // ================= LÀM QUEN TỪ MỚI =================
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
                is_self_declared_known: false
            })
        })
        .then(res => res.json())
        .then(() => {
            updateSaveIndicator('Đã lưu');
            window.location.href = '{{ route('study.session.show', $session) }}';
        })
        .catch(err => {
            console.error(err);
            updateSaveIndicator('Lưu thất bại');
            showNetworkError();
            setSubmittingState(false);
        });
    }

    function submitSelfKnown() {
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
                is_self_declared_known: true
            })
        })
        .then(res => res.json())
        .then(() => {
            updateSaveIndicator('Đã lưu');
            window.location.href = '{{ route('study.session.show', $session) }}';
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
            window.location.href = '{{ route('study.session.show', $session) }}';
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
                btn.className = 'inline-flex items-center rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-3 py-2 text-xs font-semibold shadow-2xs hover:bg-amber-100 transition cursor-pointer';
            } else {
                icon.innerText = '⭐';
                icon.className = 'mr-1.5 text-slate-400';
                text.innerText = 'Đánh dấu khó';
                btn.className = 'inline-flex items-center rounded-xl border border-slate-300 bg-white text-slate-700 px-3 py-2 text-xs font-semibold shadow-2xs hover:bg-slate-50 transition cursor-pointer';
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

    function updateSaveIndicator(text) {
        const el = document.getElementById('saveStatusText');
        if (el) el.innerText = text;
    }

    function setSubmittingState(loading) {
        if (loading) stopCurrentAudio();
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
        if (isLearnNewMode) {
            submitFamiliarization();
        } else {
            handlePracticeButtonClick();
        }
    }

    function toggleShortcutsModal() {
        document.getElementById('shortcutsModal')?.classList.toggle('hidden');
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', function (e) {
        if (e.isComposing) {
            return;
        }

        const isInputFocused = ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName);

        // Phím Enter: Kiểm tra đáp án khi đang gõ từ hoặc bấm tiếp tục câu tiếp theo
        if (e.key === 'Enter') {
            const submitBtn = document.getElementById('submitPracticeBtn');
            if (submitBtn && !submitBtn.disabled) {
                e.preventDefault();
                handlePracticeButtonClick();
                return;
            } else if (isInputFocused) {
                const val = document.activeElement.value ? document.activeElement.value.trim() : '';
                if (val.length > 0 && submitBtn) {
                    e.preventDefault();
                    submitBtn.disabled = false;
                    handlePracticeButtonClick();
                    return;
                }
            }
        }

        // Nếu người dùng đang tập trung gõ văn bản trong ô input thì không bắt các phím tắt chọn số hoặc Space
        if (isInputFocused) {
            return;
        }

        if (e.key === ' ' && isLearnNewMode) {
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
        }
    });

    // Tự động focus vào ô nhập từ vựng khi mở bài học/luyện tập
    document.addEventListener('DOMContentLoaded', function () {
        const practiceInput = document.getElementById('practiceInputText');
        if (practiceInput && !practiceInput.disabled) {
            practiceInput.focus();
        }
    });

    let currentPlayingAudio = null;

    function playAudioUrl(url) {
        if (!url) {
            alert('Mục từ này chưa có tệp âm thanh phát âm.');
            return;
        }
        stopCurrentAudio();
        try {
            currentPlayingAudio = new Audio(url);
            currentPlayingAudio.play().catch(err => {
                console.warn('Lỗi khi phát âm thanh:', err);
            });
        } catch (e) {
            console.error('Không thể khởi tạo audio element:', e);
        }
    }

    function stopCurrentAudio() {
        if (currentPlayingAudio) {
            try {
                currentPlayingAudio.pause();
                currentPlayingAudio.currentTime = 0;
            } catch (e) {}
            currentPlayingAudio = null;
        }
        if ('speechSynthesis' in window) {
            try { window.speechSynthesis.cancel(); } catch (e) {}
        }
    }

    window.addEventListener('beforeunload', stopCurrentAudio);

    function playAudioOrSpeak(url, fallbackWord, langCode) {
        if (url) {
            playAudioUrl(url);
        } else if (fallbackWord) {
            speakWord(fallbackWord, langCode);
        } else {
            alert('Chưa có âm thanh phát âm cho từ này.');
        }
    }

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
