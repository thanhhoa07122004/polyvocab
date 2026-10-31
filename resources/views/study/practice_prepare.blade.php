@extends('layouts.app')

@section('title', 'Luyện tập: ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-8">
    <div>
        <div class="flex items-center space-x-2 text-sm text-slate-500">
            <a href="{{ route('decks.show', $deck) }}" class="hover:text-slate-800 transition">&larr; Về bộ từ: {{ $deck->name }}</a>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Luyện tập: {{ $deck->name }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Ngôn ngữ: {{ $deck->learningLanguage->name }} &bull; Giải nghĩa: {{ $deck->meaningLanguage->name }}
                </p>
            </div>
            <div class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 border border-indigo-200">
                Luyện tập tự do
            </div>
        </div>
    </div>

    <!-- Thông báo nguyên tắc SRS không bị ảnh hưởng -->
    <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-900 flex items-start space-x-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <strong class="font-semibold">Lưu ý:</strong> Phiên luyện này không thay đổi lịch ôn. Kết quả luyện tập vẫn được lưu trong lịch sử để bạn theo dõi.
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Bài học đang dở nếu có -->
    @if ($activeSession)
        <div class="rounded-3xl border border-amber-300 bg-amber-50/80 p-6 shadow-xs">
            <div class="flex items-start space-x-4">
                <div class="rounded-2xl bg-amber-200/70 p-3 text-amber-800 shrink-0">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <span class="inline-flex items-center rounded-md bg-amber-200 px-2 py-0.5 text-xs font-bold text-amber-900">
                        BẠN ĐANG HỌC DỞ
                    </span>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">
                        {{ $activeSession->deck->name }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-600">
                        Chế độ: <strong>{{ $activeSession->mode->label() }}</strong> &bull;
                        Đã học: <strong>{{ $activeSession->completedItemsCount() }}/{{ $activeSession->items->count() }} từ</strong>
                    </p>
                    <div class="mt-4 flex items-center gap-3">
                        <a href="{{ route('study.session.show', $activeSession) }}"
                           class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-700 transition">
                            Tiếp tục học &rarr;
                        </a>
                        <form method="POST" action="{{ route('study.session.abandon', $activeSession) }}"
                              onsubmit="return confirm('Kết quả đã lưu vẫn được giữ. Phần chưa học sẽ được trả về danh sách để bạn chọn lại.');">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-rose-600 transition cursor-pointer">
                                Kết thúc lần học này
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('study.practice.start') }}" class="space-y-8">
        @csrf
        <input type="hidden" name="deck_ids[]" value="{{ $deck->id }}">

        <!-- 1. Phạm vi bài học -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">1. Phạm vi bài học</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Chọn nhóm từ vựng bạn muốn luyện tập trong bộ từ này.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="selectable-card relative flex items-start space-x-3 rounded-2xl border border-slate-200 hover:border-slate-300 bg-white p-4 cursor-pointer transition has-checked:border-indigo-600 has-checked:bg-indigo-50/40 has-checked:ring-1 has-checked:ring-indigo-600">
                    <input type="radio" name="scope" value="all" {{ $scope === 'all' ? 'checked' : '' }}
                           class="mt-1 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <span class="block font-bold text-sm text-slate-900">Tất cả từ vựng</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Luyện tập ngẫu nhiên tất cả các từ có trong bộ từ này.</span>
                    </div>
                </label>

                <label class="selectable-card-amber relative flex items-start space-x-3 rounded-2xl border border-slate-200 hover:border-slate-300 bg-white p-4 cursor-pointer transition has-checked:border-amber-500 has-checked:bg-amber-50/40 has-checked:ring-1 has-checked:ring-amber-500">
                    <input type="radio" name="scope" value="difficult" {{ $scope === 'difficult' ? 'checked' : '' }}
                           class="mt-1 text-amber-600 focus:ring-amber-500">
                    <div>
                        <span class="block font-bold text-sm text-slate-900 flex items-center gap-1.5">
                            Các từ cần nhớ kỹ
                            <span class="rounded-full bg-amber-100 text-amber-800 text-[10px] px-2 py-0.5 font-bold">Khuyên dùng</span>
                        </span>
                        <span class="block text-xs text-slate-500 mt-0.5">Tập trung vào các từ bạn từng đánh dấu khó hoặc trả lời chưa đúng nhiều lần.</span>
                    </div>
                </label>

                <label class="selectable-card relative flex items-start space-x-3 rounded-2xl border border-slate-200 hover:border-slate-300 bg-white p-4 cursor-pointer transition has-checked:border-indigo-600 has-checked:bg-indigo-50/40 has-checked:ring-1 has-checked:ring-indigo-600">
                    <input type="radio" name="scope" value="studied" {{ $scope === 'studied' ? 'checked' : '' }}
                           class="mt-1 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <span class="block font-bold text-sm text-slate-900">Các từ đã học</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Chỉ luyện các từ bạn đã học qua hoặc từng ôn tập.</span>
                    </div>
                </label>

                <label class="selectable-card relative flex items-start space-x-3 rounded-2xl border border-slate-200 hover:border-slate-300 bg-white p-4 cursor-pointer transition has-checked:border-indigo-600 has-checked:bg-indigo-50/40 has-checked:ring-1 has-checked:ring-indigo-600">
                    <input type="radio" name="scope" value="unstudied" {{ $scope === 'unstudied' ? 'checked' : '' }}
                           class="mt-1 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <span class="block font-bold text-sm text-slate-900">Các từ mới chưa học</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Luyện thử các từ hoàn toàn mới để kiểm tra vốn từ trước khi học.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- 2. Số lượng từ muốn luyện -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">2. Số lượng từ muốn luyện</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Hiện có: <strong id="estimateWordsCount">{{ number_format($availableMeaningsCount) }} từ</strong> phù hợp với phạm vi đã chọn.
                </p>
            </div>

            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    @foreach ([5, 10, 20] as $preset)
                        <label class="cursor-pointer">
                            <input type="radio" name="count_preset" value="{{ $preset }}"
                                   {{ $preset === 10 ? 'checked' : '' }}
                                   onclick="document.getElementById('custom_count').value = {{ $preset }}"
                                   class="peer sr-only">
                            <span class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 transition">
                                {{ $preset }} từ
                            </span>
                        </label>
                    @endforeach

                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-slate-500">Hoặc tùy chỉnh:</span>
                        <input type="number" id="custom_count" name="count" value="10" min="1" max="50"
                               class="w-20 rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800 text-center focus:border-indigo-500 focus:ring-indigo-500">
                        <span class="text-xs text-slate-500">(Tối đa 50)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Chọn các dạng bài tập -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm space-y-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">3. Chọn các dạng bài tập</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Chọn một hoặc nhiều dạng bài để luyện tập theo sở thích của bạn.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($exerciseTypes as $key => $ex)
                    @php
                        $isEligible = $ex['is_eligible'];
                    @endphp
                    <label id="exerciseCard_{{ $key }}" class="relative flex items-start space-x-3 rounded-2xl border p-4 transition
                        {{ $isEligible ? 'border-slate-200 hover:border-slate-300 bg-white cursor-pointer exercise-card-eligible has-checked:border-indigo-600 has-checked:bg-indigo-50/30 has-checked:ring-1 has-checked:ring-indigo-600' : 'border-slate-200/50 bg-slate-100/60 cursor-not-allowed exercise-card-disabled' }}">
                        <input type="checkbox" id="exerciseCheckbox_{{ $key }}" name="exercise_types[]" value="{{ $key }}"
                               {{ $isEligible && in_array($key, ['flashcard', 'multiple_choice_term_to_meaning'], true) ? 'checked' : '' }}
                               {{ ! $isEligible ? 'disabled' : '' }}
                               class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        <div class="flex-1 text-xs">
                            <span class="block font-bold text-sm text-slate-900 {{ ! $isEligible ? 'opacity-85' : '' }}">{{ $ex['name'] }}</span>
                            <span class="block text-slate-600 mt-0.5 {{ ! $isEligible ? 'opacity-85' : '' }}">{{ $ex['description'] }}</span>
                            @if (! empty($ex['example']))
                                <span class="block font-mono text-slate-400 mt-1 text-[11px] {{ ! $isEligible ? 'opacity-75' : '' }}">{{ $ex['example'] }}</span>
                            @endif
                            @if (! empty($ex['requires_audio']))
                                @if (! empty($ex['has_server_audio']))
                                    <span class="inline-flex items-center gap-1 mt-1.5 font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md text-[11px]">
                                        <span>✓ Đã có phát âm Azure TTS</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 mt-1.5 font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md text-[11px]">
                                        <span>🔊 Giọng đọc trình duyệt</span>
                                    </span>
                                @endif
                            @endif
                            @if (! $isEligible)
                                <span class="inline-flex items-center gap-1 mt-2 font-medium text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-lg text-[11px] exercise-disabled-badge">
                                    <span class="text-xs">ℹ</span>
                                    <span>{{ $ex['disabled_reason'] }}</span>
                                </span>
                            @endif

                            @if ($key === 'fill_in_the_blank')
                                @php
                                    $isAiConfigured = app(\App\Services\Ai\AiSentenceService::class)->isConfigured();
                                @endphp
                                <div class="mt-3 pt-2.5 border-t border-slate-200/80" onclick="event.stopPropagation()">
                                    <label class="flex items-start gap-2 cursor-pointer select-none">
                                        <input type="checkbox"
                                               name="use_ai_cloze"
                                               id="useAiClozeCheckbox"
                                               value="1"
                                               {{ ! $isAiConfigured || ! $isEligible ? 'disabled' : '' }}
                                               class="mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500 h-4 w-4 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <div class="text-xs">
                                            <div class="flex items-center gap-1.5 font-bold text-slate-800">
                                                <span>✨ Dùng AI đặt câu</span>
                                                @if (! $isAiConfigured)
                                                    <span class="rounded-full bg-slate-100 text-slate-500 border border-slate-200 text-[10px] px-2 py-0.2 font-semibold">Chưa thiết lập AI</span>
                                                @else
                                                    <span class="rounded-full bg-purple-100 text-purple-700 text-[10px] px-2 py-0.2 font-bold">Mới</span>
                                                @endif
                                            </div>
                                            <div class="text-slate-500 mt-0.5 text-[11px] leading-relaxed">
                                                Tạo câu mới theo từ đang luyện. Cách trả lời sẽ thay đổi giữa nhập từ và chọn đáp án.
                                            </div>
                                            <div class="text-slate-400 mt-0.5 text-[10px] italic">
                                                Nội dung từ vựng được gửi đến dịch vụ AI để tạo câu.
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Nút bắt đầu -->
        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('decks.show', $deck) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">
                &larr; Quay lại bộ từ
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-2xl bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer">
                Bắt đầu luyện tập &rarr;
            </button>
        </div>
    </form>
</div>

<script>
    function updatePracticeEstimates() {
        const scopeRadio = document.querySelector('input[name="scope"]:checked');
        const scope = scopeRadio ? scopeRadio.value : 'all';

        const estimateTextEl = document.getElementById('estimateWordsCount');
        if (estimateTextEl) estimateTextEl.innerText = 'Đang tính...';

        fetch('{{ route('study.practice.estimate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ deck_ids: [{{ $deck->id }}], scope: scope })
        })
        .then(res => res.json())
        .then(data => {
            if (estimateTextEl) {
                estimateTextEl.innerText = `${(data.available_count || 0).toLocaleString()} từ`;
            }
        })
        .catch(err => {
            console.error('Lỗi khi tính toán số từ:', err);
        });
    }

    function syncAiClozeState() {
        const fillInTheBlankCheckbox = document.getElementById('exerciseCheckbox_fill_in_the_blank');
        const aiClozeCheckbox = document.getElementById('useAiClozeCheckbox');
        const isAiConfigured = {{ app(\App\Services\Ai\AiSentenceService::class)->isConfigured() ? 'true' : 'false' }};

        if (!aiClozeCheckbox) return;

        if (!fillInTheBlankCheckbox || !fillInTheBlankCheckbox.checked || fillInTheBlankCheckbox.disabled || !isAiConfigured) {
            aiClozeCheckbox.checked = false;
            aiClozeCheckbox.disabled = true;
        } else {
            aiClozeCheckbox.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[name="scope"]').forEach(rb => {
            rb.addEventListener('change', updatePracticeEstimates);
        });

        const fillInTheBlankCheckbox = document.getElementById('exerciseCheckbox_fill_in_the_blank');
        if (fillInTheBlankCheckbox) {
            fillInTheBlankCheckbox.addEventListener('change', syncAiClozeState);
        }
        syncAiClozeState();
    });
</script>
@endsection

