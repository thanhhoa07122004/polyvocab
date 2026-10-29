@extends('layouts.app')

@section('title', 'Sửa từ: ' . $entry->term . ' | ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('decks.index') }}" class="hover:text-indigo-600">Bộ từ của tôi</a>
                <span>&rsaquo;</span>
                <a href="{{ route('decks.show', $deck) }}" class="hover:text-indigo-600 truncate max-w-[200px]">{{ $deck->name }}</a>
                <span>&rsaquo;</span>
                <a href="{{ route('decks.entries.show', [$deck, $entry]) }}" class="hover:text-indigo-600 truncate max-w-[150px]">{{ $entry->term }}</a>
                <span>&rsaquo;</span>
                <span>Chỉnh sửa</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Chỉnh sửa mục từ vựng
            </h1>
        </div>
        <a href="{{ route('decks.entries.show', [$deck, $entry]) }}"
           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Xem mục từ
        </a>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <form method="POST" action="{{ route('decks.entries.update', [$deck, $entry]) }}">
            @csrf
            @method('PUT')

            @include('decks.entries._form')
        </form>
    </div>
</div>
@endsection

