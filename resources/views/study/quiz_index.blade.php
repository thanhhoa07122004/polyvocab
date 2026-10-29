@extends('layouts.app')

@section('title', 'Chuẩn bị kiểm tra kiến thức | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-8">
    <div>
        <div class="flex items-center space-x-2 text-sm text-slate-500">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-800 transition">Bảng điều khiển</a>
            <span>/</span>
            <span class="font-semibold text-slate-800">Kiểm tra</span>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Kiểm tra đánh giá kiến thức
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Đánh giá mức độ ghi nhớ khách quan qua các bài kiểm tra tùy biến phạm vi, thời gian và dạng bài.
                </p>
            </div>
            <div class="inline-flex items-center self-start rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">
                Đánh giá khách quan &bull; Không đổi lịch SRS
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

    <!-- Cảnh báo nếu đang có bài kiểm tra dở dang -->
    @if ($activeQuizSession)
        <div class="rounded-3xl border border-amber-300 bg-amber-50/80 p-6 shadow-xs">
            <div class="flex items-start space-x-4">
                <div class="rounded-2xl bg-amber-200/70 p-3 text-amber-800 shrink-0">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <span class="inline-flex items-center rounded-md bg-amber-200 px-2 py-0.5 text-xs font-bold text-amber-900">
                        Bài kiểm tra chưa nộp
                    </span>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">
                        {{ $activeQuizSession->deck->name }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-600">
                        Chế độ: <span class="font-semibold">{{ $activeQuizSession->settings['quiz_type'] === 'practice' ? 'Luyện kiểm tra' : 'Bài kiểm tra chính thức' }}</span> &bull;
                        Thời điểm bắt đầu: {{ $activeQuizSession->created_at ? $activeQuizSession->created_at->format('H:i d/m/Y') : '' }}
                    </p>
                    <div class="mt-4 flex items-center gap-3">
                        <a href="{{ route('study.quiz.show', $activeQuizSession) }}"
                           class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-emerald-700 transition">
                            Tiếp tục làm bài &rarr;
                        </a>
                        <form method="POST" action="{{ route('study.session.abandon', $activeQuizSession) }}"
                              onsubmit="return confirm('Bạn có chắc muốn hủy bài kiểm tra này?');">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-rose-600 transition cursor-pointer">
                                Hủy bài kiểm tra cũ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Form cấu hình bài kiểm tra -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('study.quiz.start') }}" class="space-y-8">
            @csrf

            <!-- 1. Chọn bộ từ -->
            <div class="space-y-3">
                <label class="block text-sm font-bold text-slate-900">
                    1. Chọn một hoặc nhiều bộ từ tham gia kiểm tra:
                </label>
                @if ($decks->isEmpty())
                    <p class="text-xs text-slate-500">Bạn chưa có bộ từ nào. Hãy tạo bộ từ trước.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-56 overflow-y-auto p-1">
                        @foreach ($decks as $deck)
                            <label class="flex items-start space-x-3 rounded-2xl border border-slate-200 p-3 hover:border-emerald-300 hover:bg-slate-50/50 cursor-pointer transition">
                                <input type="checkbox"
                                       name="deck_ids[]"
                                       value="{{ $deck->id }}"
                                       checked
                                       class="mt-1 h-4 w-4 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <div class="min-w-0 flex-1 text-xs">
                                    <div class="font-bold text-slate-800 truncate">{{ $deck->name }}</div>
                                    <div class="text-slate-500 mt-0.5">
                                        {{ $deck->learningLanguage->name }} &bull; {{ $deck->entries_count }} mục từ
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('deck_ids')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 2. Chế độ kiểm tra & Phạm vi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6 border-t border-slate-100">
                <!-- Chế độ -->
                <div class="space-y-3">
                    <label class="block text-sm font-bold text-slate-900">
                        2. Chế độ kiểm tra:
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-start space-x-3 rounded-2xl border border-slate-200 p-3.5 hover:border-emerald-300 hover:bg-emerald-50/30 cursor-pointer transition">
                            <input type="radio" name="quiz_type" value="formal" checked class="mt-0.5 h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                            <div class="text-xs">
                                <span class="font-bold text-slate-900">Bài kiểm tra (Formal Test)</span>
                                <p class="text-slate-500 mt-0.5">Không lộ đáp án trước khi nộp, có bảng số câu và đánh dấu xem lại, đếm ngược thời gian nếu có.</p>
                            </div>
                        </label>

                        <label class="flex items-start space-x-3 rounded-2xl border border-slate-200 p-3.5 hover:border-emerald-300 hover:bg-emerald-50/30 cursor-pointer transition">
                            <input type="radio" name="quiz_type" value="practice" class="mt-0.5 h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                            <div class="text-xs">
                                <span class="font-bold text-slate-900">Luyện kiểm tra (Practice Quiz)</span>
                                <p class="text-slate-500 mt-0.5">Phản hồi ngay sau từng câu kèm giải thích, cho phép xem gợi ý (nhưng không tính điểm câu có gợi ý).</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Phạm vi từ vựng -->
                <div class="space-y-3">
                    <label class="block text-sm font-bold text-slate-900">
                        3. Phạm vi từ vựng:
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-3 rounded-2xl border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="scope" value="all" checked class="h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-800">Tất cả các từ trong bộ</span>
                        </label>
                        <label class="flex items-center space-x-3 rounded-2xl border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="scope" value="studied" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-800">Chỉ các nghĩa đã từng học</span>
                        </label>
                        <label class="flex items-center space-x-3 rounded-2xl border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="scope" value="unstudied" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-800">Chỉ các nghĩa mới chưa học</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 3. Số lượng câu hỏi & Thời gian làm bài -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6 border-t border-slate-100">
                <!-- Số câu hỏi -->
                <div class="space-y-3">
                    <label for="quizCountInput" class="block text-sm font-bold text-slate-900">
                        4. Số lượng câu hỏi:
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($presets as $p)
                            <button type="button"
                                    onclick="document.getElementById('quizCountInput').value = {{ $p }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 hover:border-emerald-300 transition cursor-pointer">
                                {{ $p }} câu
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="number"
                               id="quizCountInput"
                               name="count"
                               value="{{ old('count', $defaultCount) }}"
                               min="1"
                               max="{{ $maxCount }}"
                               required
                               class="w-24 rounded-xl border border-slate-300 px-3 py-2 text-center text-sm font-bold text-slate-900 shadow-2xs focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <span class="text-xs text-slate-500">câu (Tối đa {{ $maxCount }} câu)</span>
                    </div>
                </div>

                <!-- Giới hạn thời gian -->
                <div class="space-y-3">
                    <label for="timeLimitSelect" class="block text-sm font-bold text-slate-900">
                        5. Giới hạn thời gian:
                    </label>
                    <select id="timeLimitSelect" name="time_limit"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-2xs focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Không giới hạn thời gian</option>
                        <option value="5">5 phút</option>
                        <option value="10">10 phút</option>
                        <option value="15" selected>15 phút</option>
                        <option value="30">30 phút</option>
                        <option value="60">60 phút</option>
                    </select>
                    <p class="text-2xs text-slate-500">Hết giờ server sẽ tự động thu bài và chấm điểm các đáp án đã lưu.</p>
                </div>
            </div>

            <!-- 4. Tùy chọn xáo trộn -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox"
                           name="shuffle"
                           value="1"
                           checked
                           class="h-4 w-4 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs font-semibold text-slate-800">
                        Xáo trộn thứ tự câu hỏi và các phương án lựa chọn
                    </span>
                </label>
            </div>

            <!-- Nút Bắt đầu kiểm tra -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 transition">
                    &larr; Về bảng điều khiển
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 transition focus:outline-none focus:ring-2 focus:ring-emerald-600/20 cursor-pointer">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Bắt đầu làm bài &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
