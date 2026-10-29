@extends('layouts.app')

@section('title', 'Kết quả bài kiểm tra | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-8">
    <!-- Header kết quả -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm text-center">
        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
            ✓ Đã hoàn tất bài kiểm tra
        </span>

        <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
            Kết quả bài kiểm tra
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Chế độ: <strong class="text-slate-800">{{ $quizType === 'practice' ? 'Luyện kiểm tra' : 'Bài kiểm tra chính thức' }}</strong> &bull;
            Hoàn thành lúc: {{ $session->updated_at ? $session->updated_at->format('H:i d/m/Y') : '' }}
        </p>

        <!-- Thống kê điểm số nổi bật -->
        <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-3 text-left">
            <div class="rounded-2xl bg-indigo-50/70 p-4 border border-indigo-100">
                <div class="text-xs font-medium text-indigo-700">Tỷ lệ chính xác</div>
                <div class="mt-1 text-3xl font-black text-indigo-900">
                    {{ $summary['percentage'] ?? 0 }}%
                </div>
                <div class="mt-1 text-2xs text-indigo-600 font-semibold">
                    {{ $summary['score_achieved'] ?? 0 }} / {{ $summary['total_questions'] ?? 0 }} điểm
                </div>
            </div>

            <div class="rounded-2xl bg-emerald-50/70 p-4 border border-emerald-100">
                <div class="text-xs font-medium text-emerald-700">Câu trả lời đúng</div>
                <div class="mt-1 text-3xl font-black text-emerald-900">
                    {{ $summary['correct_count'] ?? 0 }}
                </div>
                <div class="mt-1 text-2xs text-emerald-700">Đạt điểm tuyệt đối</div>
            </div>

            <div class="rounded-2xl bg-rose-50/70 p-4 border border-rose-100">
                <div class="text-xs font-medium text-rose-700">Câu sai / Bỏ trống</div>
                <div class="mt-1 text-3xl font-black text-rose-900">
                    {{ ($summary['incorrect_count'] ?? 0) + ($summary['empty_count'] ?? 0) }}
                </div>
                <div class="mt-1 text-2xs text-rose-600">
                    {{ $summary['empty_count'] ?? 0 }} câu chưa trả lời
                </div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                <div class="text-xs font-medium text-slate-500">Thời gian làm bài</div>
                <div class="mt-1 text-2xl font-black text-slate-800">
                    @php
                        $secs = $summary['duration_seconds'] ?? 0;
                        $m = floor($secs / 60);
                        $s = $secs % 60;
                    @endphp
                    {{ $m }}p {{ $s }}s
                </div>
                <div class="mt-1 text-2xs text-slate-500">
                    @if (!empty($summary['hinted_count']))
                        {{ $summary['hinted_count'] }} câu có dùng gợi ý
                    @else
                        Không dùng gợi ý
                    @endif
                </div>
            </div>
        </div>

        <!-- Điều hướng hành động -->
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('study.quiz.index') }}"
               class="inline-flex items-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-emerald-700 transition">
                + Làm bài kiểm tra mới
            </a>

            @if (($summary['incorrect_count'] ?? 0) > 0)
                <a href="{{ route('study.review.index') }}"
                   class="inline-flex items-center rounded-xl border border-rose-300 bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-700 shadow-2xs hover:bg-rose-100 transition">
                    🔥 Luyện lại các câu sai (Luyện thêm)
                </a>
            @endif

            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                Về bảng điều khiển
            </a>
        </div>
    </div>

    <!-- Phân loại kết quả theo dạng bài -->
    @if (!empty($summary['type_breakdown']))
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-900">Kết quả phân theo dạng bài</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($summary['type_breakdown'] as $tKey => $tData)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800">
                                {{ config("polyvocab.study.exercise_types.{$tKey}.name", $tKey) }}
                            </span>
                            <span class="font-semibold {{ $tData['correct'] === $tData['total'] ? 'text-emerald-700' : 'text-slate-600' }}">
                                {{ $tData['correct'] }}/{{ $tData['total'] }} câu đúng
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-emerald-500"
                                 style="width: {{ $tData['total'] > 0 ? ($tData['correct'] / $tData['total']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Danh sách chi tiết từng câu hỏi, câu trả lời và lời giải -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-slate-900">Chi tiết từng câu hỏi & Lời giải:</h2>

        <div class="space-y-3">
            @foreach ($questions as $idx => $q)
                @php
                    $eval = $evaluatedResults[$idx] ?? [];
                    $isCorrect = $eval['is_correct'] ?? false;
                    $isEmpty = $eval['is_empty'] ?? false;
                @endphp
                <div class="rounded-2xl border p-5 transition bg-white {{ $isCorrect ? 'border-emerald-200' : ($isEmpty ? 'border-amber-200 bg-amber-50/20' : 'border-rose-200 bg-rose-50/10') }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center space-x-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg text-xs font-bold {{ $isCorrect ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ $idx + 1 }}
                            </span>
                            <span class="text-xs font-bold uppercase text-slate-500">
                                {{ $q['prompt'] }}
                            </span>
                        </div>

                        <div>
                            @if ($isCorrect)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-200">
                                    ✓ Chính xác (+{{ $eval['score'] ?? 1 }} điểm)
                                </span>
                            @elseif ($isEmpty)
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                    Bỏ trống (0 điểm)
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 border border-rose-200">
                                    ✗ Chưa đúng (0 điểm)
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Nội dung câu hỏi -->
                    <div class="mt-3">
                        <div class="text-base font-extrabold text-slate-900 font-serif">
                            {{ $q['question_text'] ?? ($q['term'] ?? ($q['cloze_sentence'] ?? '')) }}
                        </div>
                    </div>

                    <!-- Đối chiếu câu trả lời -->
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-3 border-t border-slate-100">
                        <div class="rounded-xl p-3 {{ $isCorrect ? 'bg-emerald-50 text-emerald-900' : 'bg-rose-50 text-rose-900' }}">
                            <span class="font-bold">Câu trả lời của bạn:</span>
                            <div class="mt-0.5 font-medium">
                                {{ $eval['user_answer'] ?: '(Bỏ trống không trả lời)' }}
                            </div>
                        </div>

                        <div class="rounded-xl p-3 bg-slate-50 text-slate-800 border border-slate-100">
                            <span class="font-bold">Đáp án chuẩn:</span>
                            <div class="mt-0.5 font-medium text-emerald-700">
                                {{ $eval['correct_answer'] ?? ($q['correct_answer'] ?? $q['meaning']) }}
                            </div>
                        </div>
                    </div>

                    <!-- Giải thích & Ngữ cảnh -->
                    @if (!empty($q['explanation']))
                        <div class="mt-2 text-2xs text-slate-500 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            <span class="font-bold">Ngữ cảnh / Giải thích:</span> {{ $q['explanation'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

