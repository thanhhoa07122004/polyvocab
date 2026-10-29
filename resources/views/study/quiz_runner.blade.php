@extends('layouts.app')

@section('title', ($quizType === 'practice' ? 'Luyện kiểm tra' : 'Bài kiểm tra') . ' | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-6 pb-24 sm:pb-12">
    <!-- Thanh điều hướng & Đếm ngược thời gian -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold {{ $quizType === 'practice' ? 'bg-sky-100 text-sky-800' : 'bg-emerald-100 text-emerald-800' }}">
                {{ $quizType === 'practice' ? 'Luyện kiểm tra (Có giải thích)' : 'Bài kiểm tra chính thức' }}
            </span>
            <span class="text-xs text-slate-500 font-medium">
                Tổng: <span class="font-bold text-slate-900">{{ $totalCount }} câu</span>
            </span>
        </div>

        <div class="flex items-center space-x-4">
            <!-- Trạng thái lưu (Auto-save) -->
            <div id="saveStatusIndicator" class="flex items-center space-x-1.5 text-xs text-slate-500">
                <span id="saveStatusDot" class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span id="saveStatusText">Đã lưu tự động</span>
            </div>

            <!-- Đồng hồ đếm ngược nếu có giới hạn thời gian -->
            @if ($expiresAt)
                <div class="flex items-center space-x-1.5 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-mono font-bold text-slate-800 border border-slate-200" id="timerBox">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="countdownDisplay">--:--</span>
                </div>
            @endif

            <!-- Nút Nộp bài -->
            <button type="button"
                    onclick="openSubmitModal()"
                    class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-emerald-700 transition cursor-pointer">
                Nộp bài &rarr;
            </button>
        </div>
    </div>

    <!-- Bảng câu hỏi (Question Palette Grid) -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
        <div class="flex items-center justify-between mb-3 text-xs">
            <span class="font-bold uppercase tracking-wider text-slate-500">Danh sách câu hỏi:</span>
            <div class="flex items-center space-x-3 text-2xs text-slate-500">
                <span class="flex items-center"><span class="h-2.5 w-2.5 rounded-md bg-indigo-600 mr-1"></span> Đã làm</span>
                <span class="flex items-center"><span class="h-2.5 w-2.5 rounded-md bg-slate-200 mr-1"></span> Chưa làm</span>
                <span class="flex items-center"><span class="h-2.5 w-2.5 rounded-md border-2 border-amber-400 bg-amber-50 mr-1"></span> Đánh dấu</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2" id="paletteContainer">
            @for ($i = 0; $i < $totalCount; $i++)
                <button type="button"
                        id="paletteBtn_{{ $i }}"
                        onclick="goToQuestion({{ $i }})"
                        class="palette-btn flex h-9 w-9 items-center justify-center rounded-xl border text-xs font-bold transition cursor-pointer {{ isset($answers[$i]) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }} {{ !empty($flagged[$i]) ? 'ring-2 ring-amber-400 border-amber-400 bg-amber-50' : '' }}">
                    {{ $i + 1 }}
                </button>
            @endfor
        </div>
    </div>

    <!-- Khung nội dung câu hỏi hiện tại -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        @foreach ($questions as $idx => $q)
            <div id="questionCard_{{ $idx }}" class="question-card {{ $idx === 0 ? '' : 'hidden' }} space-y-6">
                <!-- Tiêu đề câu hỏi & Nút đánh dấu xem lại -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-50 text-xs font-extrabold text-indigo-700">
                            {{ $idx + 1 }}
                        </span>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            {{ $q['prompt'] }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="button"
                                onclick="toggleFlag({{ $idx }})"
                                id="flagBtn_{{ $idx }}"
                                class="inline-flex items-center rounded-xl border px-3 py-1.5 text-xs font-semibold transition cursor-pointer {{ !empty($flagged[$idx]) ? 'border-amber-400 bg-amber-50 text-amber-900' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                            <span class="mr-1 text-amber-500">🚩</span>
                            <span id="flagText_{{ $idx }}">{{ !empty($flagged[$idx]) ? 'Đã đánh dấu' : 'Đánh dấu xem lại' }}</span>
                        </button>

                        @if ($quizType === 'practice')
                            <button type="button"
                                    onclick="useHint({{ $idx }})"
                                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-amber-50 hover:text-amber-800 transition cursor-pointer">
                                💡 Gợi ý
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Nội dung hiển thị theo dạng bài -->
                <div class="py-2">
                    @if ($q['type'] === 'multiple_choice_term_to_meaning' || $q['type'] === 'multiple_choice_meaning_to_term')
                        <div class="text-center py-4">
                            <h3 class="text-3xl font-extrabold tracking-tight text-slate-900 font-serif">
                                {{ $q['question_text'] ?? $q['term'] }}
                            </h3>
                            @if (!empty($q['pronunciation']))
                                <div class="mt-1 text-sm font-mono text-slate-500">/{{ $q['pronunciation'] }}/</div>
                            @endif
                        </div>

                        <!-- Các lựa chọn xếp dọc -->
                        <div class="space-y-3 mt-4">
                            @foreach ($q['options'] as $optIdx => $opt)
                                @php
                                    $optVal = $q['type'] === 'multiple_choice_term_to_meaning' ? (string)$opt['id'] : $opt['text'];
                                    $isSelected = isset($answers[$idx]) && (string)$answers[$idx] === $optVal;
                                @endphp
                                <button type="button"
                                        onclick="selectChoice({{ $idx }}, '{{ addslashes($optVal) }}', this)"
                                        class="choice-btn_{{ $idx }} w-full text-left rounded-2xl border-2 p-4 text-sm font-semibold transition flex items-center justify-between cursor-pointer {{ $isSelected ? 'border-indigo-600 bg-indigo-50/70 text-indigo-950' : 'border-slate-200 bg-white text-slate-800 hover:border-indigo-200 hover:bg-slate-50' }}">
                                    <div class="flex items-center space-x-3">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">
                                            {{ chr(65 + $optIdx) }}
                                        </span>
                                        <span>{{ $opt['text'] }}</span>
                                    </div>
                                    <span class="choice-icon hidden">✓</span>
                                </button>
                            @endforeach
                        </div>

                    @elseif ($q['type'] === 'fill_in_the_blank')
                        <div class="space-y-4">
                            <div class="rounded-2xl bg-slate-50 p-6 text-center border border-slate-200">
                                <p class="text-lg font-medium text-slate-900 leading-relaxed italic">
                                    "{{ $q['cloze_sentence'] }}"
                                </p>
                                @if (!empty($q['example_translation']))
                                    <p class="mt-2 text-xs text-slate-500">({{ $q['example_translation'] }})</p>
                                @endif
                            </div>

                            <div class="space-y-1.5">
                                <label for="input_{{ $idx }}" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                                    Nhập từ còn thiếu:
                                </label>
                                <input type="text"
                                       id="input_{{ $idx }}"
                                       value="{{ $answers[$idx] ?? '' }}"
                                       autocomplete="off"
                                       autocorrect="off"
                                       spellcheck="false"
                                       oninput="saveInputAnswer({{ $idx }}, this.value)"
                                       placeholder="Gõ từ vào đây..."
                                       class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-base font-semibold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>

                    @elseif ($q['type'] === 'type_the_word' || $q['type'] === 'listen_and_type')
                        <div class="space-y-4 text-center">
                            @if ($q['type'] === 'listen_and_type')
                                <div class="py-4">
                                    <button type="button"
                                            onclick="speakWord('{{ addslashes($q['audio_term']) }}', '{{ $q['audio_lang'] ?? 'en' }}')"
                                            class="inline-flex items-center rounded-full bg-emerald-100 px-5 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-200 transition cursor-pointer">
                                        🔊 Bấm để nghe phát âm
                                    </button>
                                </div>
                            @else
                                <div class="py-2">
                                    <div class="text-xs text-slate-500 font-medium">Giải nghĩa:</div>
                                    <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $q['question_text'] ?? $q['meaning'] }}</h3>
                                </div>
                            @endif

                            <div class="space-y-1.5 text-left">
                                <label for="input_{{ $idx }}" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                                    Nhập từ vựng:
                                </label>
                                <input type="text"
                                       id="input_{{ $idx }}"
                                       value="{{ $answers[$idx] ?? '' }}"
                                       autocomplete="off"
                                       autocorrect="off"
                                       spellcheck="false"
                                       oninput="saveInputAnswer({{ $idx }}, this.value)"
                                       placeholder="Gõ từ chính xác..."
                                       class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-base font-semibold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>

                    @elseif ($q['type'] === 'matching_pairs')
                        <div class="space-y-3">
                            <p class="text-xs text-slate-500">Bấm một từ ở cột trái rồi bấm nghĩa tương ứng ở cột phải để nối:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="matchingContainer_{{ $idx }}">
                                <!-- Cột từ -->
                                <div class="space-y-2">
                                    <div class="text-xs font-bold text-slate-600 uppercase">Cột Từ</div>
                                    @foreach ($q['unmatched_terms'] ?? ($q['pairs'] ?? []) as $p)
                                        <button type="button"
                                                onclick="selectMatchingTerm({{ $idx }}, '{{ $p['id'] }}', this)"
                                                data-pair-id="{{ $p['id'] }}"
                                                class="matching-term-btn_{{ $idx }} w-full text-left rounded-xl border border-slate-300 bg-white p-3 text-sm font-bold text-slate-900 transition hover:border-indigo-400">
                                            {{ $p['text'] ?? $p['term'] }}
                                        </button>
                                    @endforeach
                                </div>
                                <!-- Cột nghĩa -->
                                <div class="space-y-2">
                                    <div class="text-xs font-bold text-slate-600 uppercase">Cột Nghĩa</div>
                                    @foreach ($q['unmatched_meanings'] ?? ($q['pairs'] ?? []) as $p)
                                        <button type="button"
                                                onclick="selectMatchingMeaning({{ $idx }}, '{{ $p['id'] }}', this)"
                                                data-pair-id="{{ $p['id'] }}"
                                                class="matching-meaning-btn_{{ $idx }} w-full text-left rounded-xl border border-slate-300 bg-white p-3 text-sm font-semibold text-slate-800 transition hover:border-indigo-400">
                                            {{ $p['text'] ?? $p['meaning'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Phản hồi riêng cho chế độ Luyện kiểm tra -->
                @if ($quizType === 'practice')
                    <div id="practiceFeedback_{{ $idx }}" class="hidden rounded-2xl p-4 text-sm font-medium"></div>
                    <div id="hintBox_{{ $idx }}" class="hidden rounded-2xl bg-amber-50 p-4 border border-amber-200 text-xs text-amber-900">
                        <span class="font-bold">Gợi ý:</span> {{ $q['explanation'] ?? 'Từ vựng bắt đầu bằng ký tự: ' . mb_substr($q['term'] ?? '', 0, 1) }}
                        <div class="text-2xs text-rose-700 mt-1">⚠️ Lưu ý: Khi đã sử dụng gợi ý, câu hỏi này sẽ nhận 0 điểm.</div>
                    </div>
                @endif
            </div>
        @endforeach

        <!-- Thanh điều hướng câu Trước / Sau -->
        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between">
            <button type="button"
                    id="prevBtn"
                    onclick="prevQuestion()"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition cursor-pointer">
                &larr; Câu trước
            </button>

            <span class="text-xs font-semibold text-slate-500">
                Câu <span id="currentQuestionNum" class="font-bold text-slate-900">1</span> / {{ $totalCount }}
            </span>

            <button type="button"
                    id="nextBtn"
                    onclick="nextQuestion()"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-indigo-700 transition cursor-pointer">
                Câu sau &rarr;
            </button>
        </div>
    </div>
</div>

<!-- Modal xác nhận nộp bài -->
<div id="submitModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl space-y-4">
        <h3 class="text-lg font-bold text-slate-900">Xác nhận nộp bài kiểm tra</h3>
        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 text-xs text-slate-700 space-y-1.5">
            <p>Tổng số câu hỏi: <strong class="text-slate-900">{{ $totalCount }}</strong></p>
            <p>Số câu đã làm: <strong class="text-emerald-700" id="modalAnsweredCount">0</strong></p>
            <p>Số câu chưa trả lời: <strong class="text-rose-700" id="modalUnansweredCount">0</strong></p>
            <p>Số câu đang đánh dấu xem lại: <strong class="text-amber-700" id="modalFlaggedCount">0</strong></p>
        </div>

        <p class="text-xs text-slate-500">
            Sau khi nộp bài, bạn sẽ không thể thay đổi câu trả lời. Hệ thống sẽ chấm điểm và hiển thị kết quả chi tiết.
        </p>

        <div class="flex items-center justify-end space-x-3 pt-2">
            <button type="button" onclick="closeSubmitModal()" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Quay lại làm bài
            </button>

            <form method="POST" action="{{ route('study.quiz.submit', $session) }}" id="finalSubmitForm">
                @csrf
                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-2xs hover:bg-emerald-700">
                    Xác nhận nộp bài &rarr;
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const sessionId = {{ $session->id }};
    const csrfToken = '{{ csrf_token() }}';
    const totalCount = {{ (int) $totalCount }};
    const expiresAtTimestamp = {{ $expiresAt ? $expiresAt->timestamp : 'null' }};
    const quizType = '{{ $quizType }}';

    let currentIndex = 0;
    const answers = @json($answers ?: []);
    const flagged = @json($flagged ?: []);
    const hintsUsed = @json($hintsUsed ?: []);

    let activeMatchingTerm = null;

    function goToQuestion(idx) {
        if (idx < 0 || idx >= totalCount) return;

        document.getElementById(`questionCard_${currentIndex}`)?.classList.add('hidden');
        document.getElementById(`questionCard_${idx}`)?.classList.remove('hidden');

        currentIndex = idx;
        document.getElementById('currentQuestionNum').innerText = currentIndex + 1;

        document.getElementById('prevBtn').disabled = (currentIndex === 0);
        document.getElementById('nextBtn').innerText = (currentIndex === totalCount - 1) ? 'Xem lại bài' : 'Câu sau →';
    }

    function prevQuestion() {
        if (currentIndex > 0) goToQuestion(currentIndex - 1);
    }

    function nextQuestion() {
        if (currentIndex < totalCount - 1) {
            goToQuestion(currentIndex + 1);
        } else {
            openSubmitModal();
        }
    }

    function selectChoice(qIdx, val, btn) {
        answers[qIdx] = val;

        document.querySelectorAll(`.choice-btn_${qIdx}`).forEach(el => {
            el.classList.remove('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-950');
            el.classList.add('border-slate-200', 'bg-white', 'text-slate-800');
        });

        btn.classList.add('border-indigo-600', 'bg-indigo-50/70', 'text-indigo-950');
        updatePalette(qIdx);
        persistAnswer(qIdx, val);
    }

    let inputTimeout = null;
    function saveInputAnswer(qIdx, val) {
        answers[qIdx] = val;
        updatePalette(qIdx);

        clearTimeout(inputTimeout);
        inputTimeout = setTimeout(() => {
            persistAnswer(qIdx, val);
        }, 400);
    }

    function toggleFlag(qIdx) {
        flagged[qIdx] = !flagged[qIdx];
        const btn = document.getElementById(`flagBtn_${qIdx}`);
        const text = document.getElementById(`flagText_${qIdx}`);

        if (flagged[qIdx]) {
            btn.classList.add('border-amber-400', 'bg-amber-50', 'text-amber-900');
            btn.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
            text.innerText = 'Đã đánh dấu';
        } else {
            btn.classList.remove('border-amber-400', 'bg-amber-50', 'text-amber-900');
            btn.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
            text.innerText = 'Đánh dấu xem lại';
        }

        updatePalette(qIdx);
        persistAnswer(qIdx, answers[qIdx] || null, flagged[qIdx]);
    }

    function useHint(qIdx) {
        hintsUsed[qIdx] = true;
        document.getElementById(`hintBox_${qIdx}`)?.classList.remove('hidden');
        persistAnswer(qIdx, answers[qIdx] || null, flagged[qIdx] || false, true);
    }

    function updatePalette(qIdx) {
        const pBtn = document.getElementById(`paletteBtn_${qIdx}`);
        if (!pBtn) return;

        const hasAns = answers[qIdx] !== undefined && answers[qIdx] !== null && answers[qIdx] !== '';
        const isFlag = !!flagged[qIdx];

        if (hasAns) {
            pBtn.className = 'palette-btn flex h-9 w-9 items-center justify-center rounded-xl border text-xs font-bold transition cursor-pointer bg-indigo-600 text-white border-indigo-600';
        } else {
            pBtn.className = 'palette-btn flex h-9 w-9 items-center justify-center rounded-xl border text-xs font-bold transition cursor-pointer bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100';
        }

        if (isFlag) {
            pBtn.classList.add('ring-2', 'ring-amber-400', 'border-amber-400', 'bg-amber-50');
        }
    }

    function persistAnswer(qIdx, answerVal, isFlagged = null, usedHint = null) {
        const indicator = document.getElementById('saveStatusText');
        const dot = document.getElementById('saveStatusDot');
        if (indicator) indicator.innerText = 'Đang lưu...';
        if (dot) dot.className = 'h-2 w-2 rounded-full bg-amber-400 animate-pulse';

        fetch(`/study/quiz/${sessionId}/save-answer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question_index: qIdx,
                answer: answerVal,
                is_flagged: isFlagged !== null ? isFlagged : (flagged[qIdx] || false),
                used_hint: usedHint !== null ? usedHint : (hintsUsed[qIdx] || false)
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.expired) {
                alert('Bài kiểm tra đã hết giờ!');
                document.getElementById('finalSubmitForm').submit();
                return;
            }
            if (indicator) indicator.innerText = 'Đã lưu tự động';
            if (dot) dot.className = 'h-2 w-2 rounded-full bg-emerald-500';
        })
        .catch(err => {
            console.error(err);
            if (indicator) indicator.innerText = 'Lưu thất bại (sẽ thử lại)';
            if (dot) dot.className = 'h-2 w-2 rounded-full bg-rose-500';
        });
    }

    function openSubmitModal() {
        let answered = 0;
        let flagCount = 0;

        for (let i = 0; i < totalCount; i++) {
            if (answers[i] !== undefined && answers[i] !== null && answers[i] !== '') answered++;
            if (flagged[i]) flagCount++;
        }

        document.getElementById('modalAnsweredCount').innerText = answered;
        document.getElementById('modalUnansweredCount').innerText = (totalCount - answered);
        document.getElementById('modalFlaggedCount').innerText = flagCount;
        document.getElementById('submitModal').classList.remove('hidden');
    }

    function closeSubmitModal() {
        document.getElementById('submitModal').classList.add('hidden');
    }

    // Đếm ngược thời gian
    if (expiresAtTimestamp) {
        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = expiresAtTimestamp - now;

            if (remaining <= 0) {
                document.getElementById('countdownDisplay').innerText = '00:00 (Hết giờ)';
                document.getElementById('finalSubmitForm').submit();
                return;
            }

            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            document.getElementById('countdownDisplay').innerText =
                `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
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

