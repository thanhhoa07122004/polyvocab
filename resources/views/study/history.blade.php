@extends('layouts.app')

@section('title', 'Lịch sử học tập cá nhân | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-800 transition">Bảng điều khiển</a>
                <span>/</span>
                <span class="font-semibold text-slate-800">Lịch sử cá nhân</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                Lịch sử học tập & kiểm tra
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Theo dõi toàn bộ hành trình các phiên học từ mới, ôn tập định kỳ và kết quả kiểm tra kiến thức của bạn.
            </p>
        </div>

        <div class="inline-flex items-center self-start rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
            🔒 Riêng tư cá nhân
        </div>
    </div>

    <!-- Bộ lọc chế độ & thời gian -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <!-- Tabs chế độ -->
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('study.history.index', ['timeframe' => $currentTimeframe]) }}"
               class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ empty($currentMode) ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                Tất cả các phiên
            </a>
            <a href="{{ route('study.history.index', ['mode' => 'learn_new', 'timeframe' => $currentTimeframe]) }}"
               class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $currentMode === 'learn_new' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                Học từ mới
            </a>
            <a href="{{ route('study.history.index', ['mode' => 'review', 'timeframe' => $currentTimeframe]) }}"
               class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $currentMode === 'review' ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                Ôn tập (SRS)
            </a>
            <a href="{{ route('study.history.index', ['mode' => 'quiz', 'timeframe' => $currentTimeframe]) }}"
               class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $currentMode === 'quiz' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                Kiểm tra
            </a>
        </div>

        <!-- Lọc mốc thời gian -->
        <div class="flex items-center space-x-2 text-xs">
            <span class="text-slate-500">Thời gian:</span>
            <a href="{{ route('study.history.index', ['mode' => $currentMode, 'timeframe' => '7days']) }}"
               class="rounded-lg px-2.5 py-1 font-medium transition {{ $currentTimeframe === '7days' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                7 ngày qua
            </a>
            <a href="{{ route('study.history.index', ['mode' => $currentMode, 'timeframe' => '30days']) }}"
               class="rounded-lg px-2.5 py-1 font-medium transition {{ $currentTimeframe === '30days' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                30 ngày qua
            </a>
            <a href="{{ route('study.history.index', ['mode' => $currentMode]) }}"
               class="rounded-lg px-2.5 py-1 font-medium transition {{ empty($currentTimeframe) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Tất cả
            </a>
        </div>
    </div>

    <!-- Danh sách các phiên học tập -->
    @if ($sessions->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="mt-3 text-base font-bold text-slate-900">Chưa có lịch sử học tập phù hợp</h3>
            <p class="mt-1 text-xs text-slate-500">Các phiên học từ mới, ôn tập hoặc làm bài kiểm tra sẽ được lưu lại tại đây.</p>
            <div class="mt-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs hover:bg-indigo-500 transition">
                    Về bảng điều khiển &rarr;
                </a>
            </div>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($sessions as $sess)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:border-indigo-200 hover:shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start space-x-3.5">
                        <div class="rounded-2xl p-3 shrink-0 {{ $sess->mode === \App\Enums\StudyMode::LearnNew ? 'bg-indigo-50 text-indigo-700' : ($sess->mode === \App\Enums\StudyMode::Review ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">
                            @if ($sess->mode === \App\Enums\StudyMode::LearnNew)
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            @elseif ($sess->mode === \App\Enums\StudyMode::Review)
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-xs {{ $sess->mode === \App\Enums\StudyMode::LearnNew ? 'text-indigo-700' : ($sess->mode === \App\Enums\StudyMode::Review ? 'text-amber-800' : 'text-emerald-700') }}">
                                    {{ $sess->mode->label() }}
                                </span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-2xs text-slate-500">
                                    {{ $sess->created_at ? $sess->created_at->format('H:i d/m/Y') : '' }}
                                </span>
                            </div>

                            <h3 class="mt-1 text-base font-bold text-slate-900">
                                {{ $sess->deck->name ?? 'Bộ từ' }}
                            </h3>

                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                <span>Quy mô: <strong>{{ $sess->items_count }} nghĩa</strong></span>
                                @if ($sess->mode === \App\Enums\StudyMode::Quiz && !empty($sess->settings['score_summary']['percentage']))
                                    <span class="text-slate-300">&bull;</span>
                                    <span>Điểm: <strong class="text-emerald-700">{{ $sess->settings['score_summary']['percentage'] }}%</strong></span>
                                @endif
                                <span class="text-slate-300">&bull;</span>
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-2xs font-semibold {{ $sess->status === \App\Enums\StudySessionStatus::Completed ? 'bg-emerald-50 text-emerald-700' : ($sess->status === \App\Enums\StudySessionStatus::InProgress ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $sess->status->label() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 self-end sm:self-center">
                        @if ($sess->mode === \App\Enums\StudyMode::Quiz)
                            @if ($sess->status === \App\Enums\StudySessionStatus::Completed)
                                <a href="{{ route('study.quiz.result', $sess) }}"
                                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                                    Xem kết quả &rarr;
                                </a>
                            @else
                                <a href="{{ route('study.quiz.show', $sess) }}"
                                   class="inline-flex items-center rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 transition">
                                    Làm tiếp &rarr;
                                </a>
                            @endif
                        @else
                            @if ($sess->status === \App\Enums\StudySessionStatus::InProgress || $sess->status === \App\Enums\StudySessionStatus::Paused)
                                <a href="{{ route('study.session.show', $sess) }}"
                                   class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-indigo-700 transition">
                                    Tiếp tục &rarr;
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
    @endif
</div>
@endsection

