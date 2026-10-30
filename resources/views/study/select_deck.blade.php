@extends('layouts.app')

@section('title', 'Chọn bộ từ để học | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 transition">
                    &larr; Bảng điều khiển
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-sm font-semibold text-slate-800">Chọn bộ từ</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                Chọn bộ từ để học từ mới
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Chọn một bộ từ bạn muốn học hôm nay để bắt đầu phiên làm quen và củng cố từ mới.
            </p>
        </div>
        <a href="{{ route('decks.create') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
            + Tạo bộ từ mới
        </a>
    </div>

    @if ($decks->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <h3 class="text-base font-bold text-slate-900">Bạn chưa có bộ từ nào</h3>
            <p class="mt-2 text-sm text-slate-500">Tạo bộ từ đầu tiên để bắt đầu lưu trữ và học tập từ vựng.</p>
            <div class="mt-6">
                <a href="{{ route('decks.create') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    + Tạo bộ từ ngay
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($decks as $deck)
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:border-indigo-300 hover:shadow-sm">
                    <div>
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span class="font-medium text-slate-700">
                                {{ $deck->learningLanguage->name }}
                                @if ($deck->learning_language_id !== $deck->meaning_language_id)
                                    &rarr; {{ $deck->meaningLanguage->name }}
                                @else
                                    (Đơn ngữ)
                                @endif
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                                {{ $deck->entries_count }} từ
                            </span>
                        </div>
                        <h2 class="mt-2 text-base font-bold text-slate-900">
                            {{ $deck->name }}
                        </h2>
                        @if ($deck->description)
                            <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ $deck->description }}</p>
                        @endif

                        <div class="mt-3 flex items-center gap-2">
                            @if ($deck->unstudied_count > 0)
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                    {{ $deck->unstudied_count }} từ mới sẵn sàng
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                    Đã học hết từ mới
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between pt-3 border-t border-slate-100">
                        <a href="{{ route('decks.show', $deck) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                            Xem từ vựng
                        </a>
                        @if ($deck->unstudied_count > 0)
                            <a href="{{ route('study.new.prepare', $deck) }}"
                               class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-indigo-700 transition">
                                Bắt đầu học &rarr;
                            </a>
                        @else
                            <a href="{{ route('decks.entries.create', $deck) }}"
                               class="inline-flex items-center rounded-xl bg-slate-100 px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition">
                                + Thêm từ vựng
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

