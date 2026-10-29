@extends('layouts.app')

@section('title', $entry->term . ' | ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumbs & Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            @auth
                @if (auth()->id() === $deck->user_id)
                    <a href="{{ route('decks.index') }}" class="hover:text-indigo-600">Bộ từ của tôi</a>
                @else
                    <a href="{{ route('explore') }}" class="hover:text-indigo-600">Khám phá</a>
                @endif
            @else
                <a href="{{ route('explore') }}" class="hover:text-indigo-600">Khám phá</a>
            @endauth
            <span>&rsaquo;</span>
            <a href="{{ route('decks.show', $deck) }}" class="hover:text-indigo-600 truncate max-w-[200px]">{{ $deck->name }}</a>
            <span>&rsaquo;</span>
            <span class="font-medium text-slate-900 truncate max-w-[150px]">{{ $entry->term }}</span>
        </div>

        <a href="{{ route('decks.show', $deck) }}"
           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Về bộ từ
        </a>
    </div>

    <!-- Khối chi tiết mục từ vựng -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-slate-100 pb-6">
            <div>
                <!-- Từ vựng gốc -->
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900"
                    lang="{{ $deck->learningLanguage->code }}"
                    dir="{{ $deck->learningLanguage->direction }}">
                    {{ $entry->term }}
                </h1>

                <!-- Phiên âm -->
                @if ($entry->pronunciation)
                    <div class="mt-2 text-base font-mono text-indigo-600"
                         lang="{{ $deck->learningLanguage->code }}"
                         dir="{{ $deck->learningLanguage->direction }}">
                        /{{ $entry->pronunciation }}/
                    </div>
                @endif
            </div>

            <!-- Nút thao tác của chủ sở hữu -->
            @can('update', $deck)
                <div class="flex items-center space-x-2 shrink-0">
                    <a href="{{ route('decks.entries.edit', [$deck, $entry]) }}"
                       class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Chỉnh sửa
                    </a>

                    <form method="POST" action="{{ route('decks.entries.destroy', [$deck, $entry]) }}"
                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa mục từ « {{ addslashes($entry->term) }} »?');"
                          class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            Xóa
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <!-- Ghi chú chung -->
        @if ($entry->note)
            <div class="rounded-2xl bg-amber-50/60 border border-amber-200/80 p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-amber-800 mb-1">Ghi chú mục từ</div>
                <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ $entry->note }}</p>
            </div>
        @endif

        <!-- Danh sách các ý nghĩa -->
        <div class="space-y-6 pt-2">
            <h2 class="text-lg font-bold text-slate-900">
                Các ý nghĩa ({{ $entry->meanings->count() }})
            </h2>

            <div class="space-y-5">
                @foreach ($entry->meanings as $meaning)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5 space-y-4">
                        <div class="flex items-start">
                            <span class="mr-3 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white font-mono text-xs font-bold">
                                {{ $meaning->sort_order }}
                            </span>

                            <div class="space-y-1 flex-1">
                                @if ($meaning->part_of_speech)
                                    <span class="inline-block rounded-md bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">
                                        {{ $meaning->part_of_speech }}
                                    </span>
                                @endif

                                @if ($meaning->meaning)
                                    <p class="text-base text-slate-900 leading-relaxed font-medium"
                                       lang="{{ $deck->meaningLanguage->code }}"
                                       dir="{{ $deck->meaningLanguage->direction }}">
                                        {{ $meaning->meaning }}
                                    </p>
                                @else
                                    <p class="text-sm italic text-slate-400">
                                        (Chưa có nội dung ý nghĩa)
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Danh sách ví dụ của nghĩa -->
                        @if ($meaning->examples->count() > 0)
                            <div class="mt-3 border-t border-slate-200/80 pt-3 pl-9 space-y-3">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ví dụ minh họa</span>

                                <div class="space-y-2.5">
                                    @foreach ($meaning->examples as $example)
                                        <div class="rounded-xl bg-white p-3 border border-slate-200/70 space-y-1">
                                            <!-- Câu ví dụ theo ngôn ngữ học -->
                                            <p class="text-sm font-medium text-slate-900"
                                               lang="{{ $deck->learningLanguage->code }}"
                                               dir="{{ $deck->learningLanguage->direction }}">
                                                {{ $example->sentence }}
                                            </p>

                                            <!-- Bản dịch câu ví dụ -->
                                            @if ($example->translation)
                                                <p class="text-xs text-slate-600"
                                                   lang="{{ $deck->meaningLanguage->code }}"
                                                   dir="{{ $deck->meaningLanguage->direction }}">
                                                    {{ $example->translation }}
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

