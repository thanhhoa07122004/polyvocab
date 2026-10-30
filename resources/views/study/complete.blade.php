@extends('layouts.app')

@section('title', 'Kết quả học tập: ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-2xl space-y-6 sm:space-y-8 text-center pb-12">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-10 shadow-sm space-y-6">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-100 text-emerald-600 shadow-xs">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <div>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                Hoàn thành lượt học &bull; {{ $session->mode->label() }}
            </span>
            <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                @if ($session->mode === \App\Enums\StudyMode::LearnNew)
                    Đã làm quen xong từ mới
                @else
                    Kết quả luyện tập & ôn tập
                @endif
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Bộ từ: <span class="font-bold text-slate-800">"{{ $deck->name }}"</span> &bull; Thời gian: <strong>{{ $activeMinutes }} phút</strong>
            </p>
        </div>

        {{-- NẾU LÀ LUỒNG HỌC TỪ MỚI (LÀM QUEN) --}}
        @if ($session->mode === \App\Enums\StudyMode::LearnNew)
            <div class="rounded-2xl p-4 text-xs text-left leading-relaxed border bg-indigo-50/70 border-indigo-200 text-indigo-900">
                <div class="flex items-start space-x-2">
                    <span class="text-base">💡</span>
                    <div>
                        <strong class="font-bold">Bạn đã làm quen thành công {{ $totalItems }} từ mới!</strong>
                        Các từ này đã được đưa vào kế hoạch ôn tập. Bây giờ bạn có thể luyện tập ngay để củng cố trí nhớ, học tiếp các từ mới khác hoặc quay về trang bộ từ.
                    </div>
                </div>
            </div>

            {{-- Thống kê số lượng làm quen (KHÔNG hiển thị điểm đúng/sai) --}}
            <div class="grid grid-cols-3 gap-3 text-left">
                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                    <div class="text-xs font-medium text-slate-500">Đã làm quen</div>
                    <div class="mt-1 text-2xl font-black text-indigo-600">{{ $totalItems }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">từ mới</div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                    <div class="text-xs font-semibold text-purple-700">Đã biết trước</div>
                    <div class="mt-1 text-2xl font-black text-purple-600">{{ $selfKnownCount }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">tự khai báo</div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                    <div class="text-xs font-semibold text-amber-700">Đánh dấu khó</div>
                    <div class="mt-1 text-2xl font-black text-amber-600">{{ $difficultCount }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">cần chú ý</div>
                </div>
            </div>

            {{-- 3 NÚT ĐIỀU HƯỚNG BẮT BUỘC THEO QUY ĐỊNH --}}
            <div class="pt-4 space-y-3">
                {{-- Nút 1: Luyện những từ vừa học --}}
                <form method="POST" action="{{ route('study.practice.from_session', $session) }}">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-purple-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-purple-500 transition cursor-pointer">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Luyện những từ vừa học ({{ $totalItems }} từ)
                    </button>
                </form>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    {{-- Nút 2: Học thêm từ mới --}}
                    <a href="{{ route('study.new.prepare', $deck) }}"
                       class="w-full sm:w-1/2 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-indigo-500 transition">
                        + Học thêm từ mới
                    </a>

                    {{-- Nút 3: Về bộ từ --}}
                    <a href="{{ route('decks.show', $deck) }}"
                       class="w-full sm:w-1/2 inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                        &larr; Về bộ từ
                    </a>
                </div>
            </div>

        {{-- NẾU LÀ LUỒNG LUYỆN TẬP HOẶC ÔN TẬP CÓ CHẤM ĐIỂM --}}
        @else
            <!-- Trạng thái cập nhật Lịch ôn -->
            <div class="rounded-2xl p-4 text-xs text-left leading-relaxed border {{ $srsUpdated ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900' : 'bg-blue-50/70 border-blue-200 text-blue-900' }}">
                <div class="flex items-start space-x-2">
                    <span class="font-bold text-sm">{{ $srsUpdated ? '✓' : 'ℹ️' }}</span>
                    <div>
                        @if ($srsUpdated)
                            <strong class="font-bold">Lịch ôn tập đã được cập nhật:</strong>
                            Các từ bạn trả lời đúng ở lần làm đầu đã được tăng chu kỳ ôn, các từ chưa nhớ sẽ được xếp lịch ôn lại sớm.
                            Hiện bộ từ còn <strong>{{ $remainingDueCount }} từ</strong> đến hạn ôn tập.
                        @else
                            <strong class="font-bold">Lịch ôn tập không thay đổi:</strong>
                            Đây là lần luyện tập tự do. Kết quả giúp bạn củng cố kiến thức mà không làm xáo trộn lịch ôn tập định kỳ đã lên trước đó.
                        @endif
                    </div>
                </div>
            </div>

            <!-- Thống kê điểm số và độ chính xác lần làm đầu -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-left">
                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                    <div class="text-xs font-medium text-slate-500">Độ chính xác (lần 1)</div>
                    <div class="mt-1 text-2xl font-black text-indigo-600">{{ $accuracyPercent }}%</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $firstAttemptPass }}/{{ $firstAttemptTotal }} từ</div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                    <div class="text-xs font-semibold text-emerald-700">Đúng ngay</div>
                    <div class="mt-1 text-2xl font-black text-emerald-600">{{ $passedPracticeCount }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        {{ $hintedCount > 0 ? "({$hintedCount} có gợi ý)" : 'Không dùng gợi ý' }}
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                    <div class="text-xs font-semibold text-rose-700">Chưa nhớ / Sai</div>
                    <div class="mt-1 text-2xl font-black text-rose-600">{{ $failedPracticeCount + $selfForgotCount }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        {{ $retriesCount > 0 ? "Đã thử lại {$retriesCount} từ" : '0 câu sai' }}
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                    <div class="text-xs font-semibold text-purple-700">Tự đánh giá</div>
                    <div class="mt-1 text-2xl font-black text-purple-600">{{ $selfRememberCount }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $difficultCount }} từ khó</div>
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
                            Từ cần củng cố thêm ({{ $needsExtraPracticeItems->count() }})
                        </span>
                        <a href="{{ route('study.practice.deck', ['deck' => $deck->id, 'scope' => 'difficult']) }}"
                           class="text-xs font-bold text-rose-700 hover:text-rose-900 underline">
                            Luyện từ khó &rarr;
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

            <!-- Các nút điều hướng tiếp theo cho Luyện tập / Ôn tập -->
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                @if ($session->mode === \App\Enums\StudyMode::Review && $remainingDueCount > 0)
                    <a href="{{ route('study.review.index', ['deck_id' => $deck->id]) }}"
                       class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-indigo-500 transition">
                        Tiếp tục ôn tập (còn {{ $remainingDueCount }} từ) &rarr;
                    </a>
                @else
                    <a href="{{ route('study.practice.deck', $deck) }}"
                       class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-indigo-500 transition">
                        Luyện tập thêm bộ này &rarr;
                    </a>
                @endif

                <a href="{{ route('decks.show', $deck) }}"
                   class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                    Về bộ từ
                </a>

                <a href="{{ route('dashboard') }}"
                   class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                    Về Bảng điều khiển
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
