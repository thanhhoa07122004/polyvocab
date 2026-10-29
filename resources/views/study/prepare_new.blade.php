@extends('layouts.app')

@section('title', 'Chuẩn bị học từ mới: ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-3xl space-y-8">
    <!-- Điều hướng & Tiêu đề -->
    <div>
        <div class="flex items-center space-x-2 text-sm text-slate-500">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-800 transition">Bảng điều khiển</a>
            <span>/</span>
            <a href="{{ route('decks.show', $deck) }}" class="hover:text-slate-800 transition">{{ $deck->name }}</a>
            <span>/</span>
            <span class="font-semibold text-slate-800">Chuẩn bị học từ mới</span>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Chuẩn bị học từ mới
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Làm quen và củng cố các nét nghĩa chưa học trong bộ từ <span class="font-semibold text-slate-800">"{{ $deck->name }}"</span>.
                </p>
            </div>
            <div class="inline-flex items-center self-start rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                Đơn vị tiến độ: Từng nghĩa riêng biệt
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

    <!-- Cảnh báo nếu đang có phiên học dở dang -->
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
                        Phiên học đang diễn ra
                    </span>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">
                        Bạn có một phiên học chưa kết thúc cho bộ từ này
                    </h2>
                    <div class="mt-2 text-sm text-slate-700 space-y-1">
                        <p>&bull; Chế độ: <span class="font-semibold">{{ $activeSession->mode->label() }}</span> (Giai đoạn {{ $activeSession->stage->label() }})</p>
                        <p>&bull; Số mục đã xử lý: <span class="font-bold text-amber-900">{{ $activeSession->completedItemsCount() }}/{{ $activeSession->items->count() }} nghĩa</span></p>
                        <p>&bull; Thời điểm tương tác gần nhất: <span class="font-medium">{{ $activeSession->last_interacted_at ? $activeSession->last_interacted_at->diffForHumans() : 'Vừa xong' }}</span></p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <a href="{{ route('study.session.show', $activeSession) }}"
                           class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-amber-700 transition">
                            Tiếp tục phiên học &rarr;
                        </a>

                        <form method="POST" action="{{ route('study.session.abandon', $activeSession) }}"
                              onsubmit="return confirm('Bạn có chắc chắn muốn kết thúc phiên học cũ? Những nghĩa bạn đã hoàn thành làm quen trước đó vẫn được lưu lại an toàn trong hệ thống.');">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-2xs hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 transition cursor-pointer">
                                Kết thúc phiên cũ để tạo phiên mới
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Thông tin cấu hình bộ từ -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-3 border-b border-slate-100 pb-6">
            <div>
                <dt class="text-xs font-medium text-slate-500">Bộ từ</dt>
                <dd class="mt-1 text-base font-bold text-slate-900 truncate">{{ $deck->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Cặp ngôn ngữ</dt>
                <dd class="mt-1 text-base font-semibold text-slate-900">
                    {{ $deck->learningLanguage->name }}
                    <span class="text-slate-400">&rarr;</span>
                    {{ $deck->meaningLanguage->name }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Nghĩa mới khả dụng</dt>
                <dd class="mt-1 text-base font-extrabold text-indigo-600">
                    {{ $availableCount }} nghĩa chưa học
                </dd>
            </div>
        </dl>

        @if ($availableCount === 0)
            <div class="mt-6 rounded-2xl bg-slate-50 p-6 text-center border border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Tuyệt vời! Bạn đã làm quen tất cả các nghĩa trong bộ từ này</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Hãy thêm từ vựng mới hoặc chuyển sang chế độ <span class="font-semibold text-amber-700">Ôn tập</span> để củng cố các từ đã học.
                </p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('decks.entries.create', $deck) }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                        + Thêm từ vựng mới
                    </a>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Về bảng điều khiển
                    </a>
                </div>
            </div>
        @else
            <!-- Form thiết lập phiên học -->
            <form method="POST" action="{{ route('study.new.start', $deck) }}" id="startStudyForm" class="mt-6 space-y-8">
                @csrf

                <!-- Khối 1: Số nghĩa muốn học -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="countInput" class="text-sm font-bold text-slate-900">
                            1. Số lượng nghĩa muốn học trong phiên:
                        </label>
                        <span class="text-xs text-slate-500">
                            Giới hạn: {{ $minBatch }} - {{ min($maxBatch, $availableCount) }} nghĩa
                        </span>
                    </div>

                    <!-- Nút chọn nhanh -->
                    <div class="flex flex-wrap items-center gap-3">
                        @foreach ($presets as $preset)
                            @if ($preset <= $availableCount)
                                <button type="button"
                                        onclick="setBatchCount({{ $preset }})"
                                        class="preset-btn inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 cursor-pointer">
                                    {{ $preset }} nghĩa
                                </button>
                            @endif
                        @endforeach
                    </div>

                    <!-- Ô nhập tùy chỉnh -->
                    <div class="mt-2 flex items-center space-x-3">
                        <div class="relative w-36">
                            <input type="number"
                                   id="countInput"
                                   name="count"
                                   value="{{ old('count', $defaultCount) }}"
                                   min="{{ $minBatch }}"
                                   max="{{ min($maxBatch, $availableCount) }}"
                                   oninput="checkAvailableNotice()"
                                   required
                                   class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-center text-sm font-bold text-slate-900 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <span class="text-sm text-slate-600">nghĩa</span>
                    </div>

                    @error('count')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    <!-- Thông báo chia nhóm nhỏ (Chunking) -->
                    <div class="rounded-2xl bg-indigo-50/70 p-4 border border-indigo-100 flex items-start space-x-3">
                        <span class="text-indigo-600 text-base">💡</span>
                        <div class="text-xs text-indigo-900 leading-relaxed">
                            <span class="font-bold">Phương pháp học hiệu quả:</span>
                            <span class="font-semibold">Chia thành nhóm nhỏ để làm quen rồi luyện nhớ</span>
                            (mặc định mỗi nhóm 5 nghĩa). Bạn sẽ hiểu kỹ từng từ trước khi chuyển sang nhóm kế tiếp.
                        </div>
                    </div>

                    <!-- Cảnh báo nếu số lượng yêu cầu lớn hơn khả dụng thực tế -->
                    <div id="lessAvailableAlert" class="hidden rounded-2xl bg-amber-50 p-4 border border-amber-200 text-xs text-amber-900">
                        Số nghĩa thực tế khả dụng trong bộ từ là <span class="font-bold">{{ $availableCount }}</span> nghĩa. Hệ thống sẽ học tối đa số lượng này.
                    </div>
                </div>

                <!-- Khối 2: Danh sách 8 dạng bài học -->
                <div class="space-y-4 pt-6 border-t border-slate-100">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            2. Các dạng bài sẽ xuất hiện khi luyện nhớ:
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Tái sử dụng các dạng câu hỏi phong phú. Dạng bài thiếu dữ liệu (ví dụ câu ví dụ hoặc âm thanh) sẽ tự động tạm vô hiệu hóa.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($exercises as $key => $ex)
                            <div class="relative flex flex-col justify-between rounded-2xl border p-4 transition {{ $ex['is_eligible'] ? 'border-slate-200 bg-white hover:border-indigo-200' : 'border-slate-200/60 bg-slate-50/70 opacity-60' }}">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-sm font-bold {{ $ex['is_eligible'] ? 'text-slate-900' : 'text-slate-500' }}">
                                            {{ $ex['name'] }}
                                        </h3>
                                        @if ($ex['is_eligible'])
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-2xs font-semibold text-emerald-700 shrink-0">
                                                ✓ Sẵn sàng
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-slate-200 px-2 py-0.5 text-2xs font-semibold text-slate-600 shrink-0">
                                                Tạm khóa
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ $ex['description'] }}
                                    </p>
                                    <div class="mt-2 rounded-lg bg-slate-50 p-2 text-2xs font-mono text-slate-600 border border-slate-100">
                                        Ví dụ: {{ $ex['example'] }}
                                    </div>
                                </div>

                                <div class="mt-3 pt-2 border-t border-slate-100 text-2xs">
                                    @if ($ex['is_eligible'])
                                        <span class="text-slate-500">
                                            Đáp ứng điều kiện: <span class="font-semibold text-indigo-600">{{ $ex['eligible_count'] }} nghĩa</span>
                                        </span>
                                    @else
                                        <span class="text-amber-700 font-medium">
                                            ⚠️ {{ $ex['disabled_reason'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Nút Bắt đầu học -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('decks.show', $deck) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                        &larr; Quay lại bộ từ
                    </a>

                    <button type="submit"
                            id="startSubmitBtn"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/20 cursor-pointer">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Bắt đầu học ngay &rarr;
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
    const availableCount = {{ (int) $availableCount }};

    function setBatchCount(val) {
        const input = document.getElementById('countInput');
        if (input) {
            input.value = Math.min(val, availableCount);
            checkAvailableNotice();
        }
    }

    function checkAvailableNotice() {
        const input = document.getElementById('countInput');
        const alert = document.getElementById('lessAvailableAlert');
        if (input && alert) {
            const val = parseInt(input.value, 10);
            if (val > availableCount) {
                alert.classList.remove('hidden');
            } else {
                alert.classList.add('hidden');
            }
        }
    }
</script>
@endsection
