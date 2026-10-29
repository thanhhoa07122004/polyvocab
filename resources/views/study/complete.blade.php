@extends('layouts.app')

@section('title', 'Hoàn thành phiên: ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-2xl space-y-8 text-center pb-12">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-10 shadow-sm space-y-6">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-100 text-emerald-600 shadow-xs">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <div>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                Phiên hoàn thành &bull; {{ $session->mode->label() }}
            </span>
            <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Tổng kết kết quả học tập
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Bộ từ: <span class="font-bold text-slate-800">"{{ $deck->name }}"</span> &bull; Thời gian ước tính: <strong>{{ $activeMinutes }} phút</strong>
            </p>
        </div>

        <!-- Trạng thái cập nhật Lịch ôn SRS -->
        <div class="rounded-2xl p-4 text-xs text-left leading-relaxed border {{ $srsUpdated ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900' : 'bg-blue-50/70 border-blue-200 text-blue-900' }}">
            <div class="flex items-start space-x-2">
                <span class="font-bold text-sm">{{ $srsUpdated ? '✓' : 'ℹ️' }}</span>
                <div>
                    @if ($srsUpdated)
                        <strong class="font-bold">Lịch ôn tập SRS đã được cập nhật:</strong>
                        Các nét nghĩa bạn trả lời đúng đã được tăng chu kỳ giãn cách, các nghĩa sai được xếp lịch ôn lại sớm.
                        Hiện bộ từ còn <strong>{{ $remainingDueCount }} nghĩa</strong> đến hạn ôn tập.
                    @else
                        <strong class="font-bold">Lịch ôn tập SRS không thay đổi:</strong>
                        Đây là phiên luyện tập tự do. Kết quả được lưu trong lịch sử luyện nhưng không làm thay đổi các mốc đến hạn SRS đã lên lịch trước đó.
                    @endif
                </div>
            </div>
        </div>

        <!-- Bảng thống kê chi tiết các chỉ số -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-left">
            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                <div class="text-xs font-medium text-slate-500">Số nghĩa đã xử lý</div>
                <div class="mt-1 text-2xl font-black text-indigo-600">{{ $totalItems }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5">Không đếm lặp</div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                <div class="text-xs font-semibold text-emerald-700">Đúng khách quan</div>
                <div class="mt-1 text-2xl font-black text-emerald-600">{{ $passedPracticeCount }}</div>
                @if ($hintedCount > 0)
                    <div class="text-[11px] text-amber-600 mt-0.5">({{ $hintedCount }} dùng gợi ý)</div>
                @else
                    <div class="text-[11px] text-slate-400 mt-0.5">Không dùng gợi ý</div>
                @endif
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                <div class="text-xs font-semibold text-rose-700">Chưa nhớ / Sai</div>
                <div class="mt-1 text-2xl font-black text-rose-600">{{ $failedPracticeCount + $selfForgotCount }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5">{{ $skippedCount }} bỏ qua</div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                <div class="text-xs font-semibold text-purple-700">Tự đánh giá</div>
                <div class="mt-1 text-2xl font-black text-purple-600">{{ $selfRememberCount + $selfKnownCount }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5">{{ $difficultCount }} đánh dấu khó</div>
            </div>
        </div>

        <!-- Danh sách các mục cần luyện thêm nếu có -->
        @if ($needsExtraPracticeItems->isNotEmpty())
            <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5 text-left space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-800 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Nghĩa cần luyện thêm ({{ $needsExtraPracticeItems->count() }})
                    </span>
                    <a href="{{ route('study.practice.deck', ['deck' => $deck->id, 'scope' => 'difficult']) }}"
                       class="text-xs font-bold text-rose-700 hover:text-rose-900 underline">
                        Luyện ngay các mục này &rarr;
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    @foreach ($needsExtraPracticeItems as $needItem)
                        <div class="rounded-xl bg-white p-3 border border-rose-200/60 shadow-2xs">
                            <div class="font-bold text-slate-900 text-sm">
                                {{ $needItem->entryMeaning->vocabularyEntry->term }}
                            </div>
                            <div class="text-slate-600 mt-0.5">
                                {{ $needItem->entryMeaning->meaning }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Lưu ý khoa học ghi nhớ (Không tự phong thành thạo) -->
        <div class="rounded-2xl bg-slate-50 p-4 text-xs text-slate-500 text-left leading-relaxed border border-slate-200">
            <span class="font-bold text-slate-700">Lưu ý khoa học:</span> Hoàn thành phiên học hôm nay giúp củng cố kiến thức ngắn hạn. Hệ thống không gán danh hiệu "thành thạo" tức thì; mức độ ghi nhớ dài hạn phụ thuộc vào việc ôn tập đúng hạn trong các chu kỳ SRS tiếp theo.
        </div>

        <!-- Các nút điều hướng tiếp theo -->
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            @if ($session->mode === \App\Enums\StudyMode::Review)
                <a href="{{ route('study.review.index', ['deck_id' => $deck->id]) }}"
                   class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-indigo-500 transition">
                    Tiếp tục ôn tập bộ này &rarr;
                </a>
            @else
                <a href="{{ route('study.new.prepare', $deck) }}"
                   class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-indigo-500 transition">
                    Học từ mới tiếp theo &rarr;
                </a>
            @endif

            <a href="{{ route('decks.show', $deck) }}"
               class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                Về trang bộ từ
            </a>

            <a href="{{ route('dashboard') }}"
               class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                Về Bảng điều khiển
            </a>
        </div>
    </div>
</div>
@endsection
