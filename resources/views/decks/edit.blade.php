@extends('layouts.app')

@section('title', 'Chỉnh sửa bộ từ: ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('decks.index') }}" class="hover:text-indigo-600">Bộ từ của tôi</a>
                <span>&rsaquo;</span>
                <a href="{{ route('decks.show', $deck) }}" class="hover:text-indigo-600 truncate max-w-[200px]">{{ $deck->name }}</a>
                <span>&rsaquo;</span>
                <span>Chỉnh sửa</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Chỉnh sửa bộ từ
            </h1>
        </div>
        <a href="{{ route('decks.show', $deck) }}"
           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Xem bộ từ
        </a>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <form method="POST" action="{{ route('decks.update', $deck) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Tên bộ từ -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-900">
                    Tên bộ từ <span class="text-rose-500">*</span>
                </label>
                <div class="mt-1">
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $deck->name) }}"
                           maxlength="150"
                           required
                           class="w-full rounded-xl border @error('name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div class="mt-1 flex justify-between text-xs text-slate-500">
                    <span>Tối đa 150 ký tự.</span>
                </div>
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Mô tả bộ từ -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-900">
                    Mô tả bộ từ <span class="text-xs text-slate-400 font-normal">(Tùy chọn)</span>
                </label>
                <div class="mt-1">
                    <textarea name="description"
                              id="description"
                              rows="3"
                              maxlength="2000"
                              class="w-full rounded-xl border @error('description') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ old('description', $deck->description) }}</textarea>
                </div>
                <div class="mt-1 flex justify-between text-xs text-slate-500">
                    <span>Tối đa 2.000 ký tự.</span>
                </div>
                @error('description')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Ngôn ngữ đang học -->
                <div>
                    <label for="learning_language_id" class="block text-sm font-medium text-slate-900">
                        Ngôn ngữ đang học <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-1">
                        <select name="learning_language_id"
                                id="learning_language_id"
                                required
                                class="w-full rounded-xl border @error('learning_language_id') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach ($activeLanguages as $lang)
                                <option value="{{ $lang->id }}" @selected(old('learning_language_id', $deck->learning_language_id) == $lang->id)>
                                    {{ $lang->name }} ({{ $lang->native_name }})
                                    @if (! $lang->is_active)
                                        (Hiện đã tắt)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('learning_language_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Ngôn ngữ giải nghĩa -->
                <div>
                    <label for="meaning_language_id" class="block text-sm font-medium text-slate-900">
                        Ngôn ngữ giải nghĩa <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-1">
                        <select name="meaning_language_id"
                                id="meaning_language_id"
                                required
                                class="w-full rounded-xl border @error('meaning_language_id') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach ($activeLanguages as $lang)
                                <option value="{{ $lang->id }}" @selected(old('meaning_language_id', $deck->meaning_language_id) == $lang->id)>
                                    {{ $lang->name }} ({{ $lang->native_name }})
                                    @if (! $lang->is_active)
                                        (Hiện đã tắt)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('meaning_language_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Chế độ hiển thị (Visibility) -->
            <div>
                <label class="block text-sm font-medium text-slate-900 mb-2">
                    Chế độ hiển thị <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex items-start p-4 rounded-2xl border cursor-pointer transition hover:bg-slate-50 @if(old('visibility', $deck->visibility->value) === 'private') border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600 @else border-slate-200 @endif">
                        <div class="flex items-center h-5">
                            <input type="radio"
                                   name="visibility"
                                   value="private"
                                   @checked(old('visibility', $deck->visibility->value) === 'private')
                                   class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="font-semibold text-slate-900 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Riêng tư (Private)
                            </span>
                            <span class="block text-xs text-slate-500 mt-0.5">
                                Chỉ mình bạn có quyền xem bộ từ này.
                            </span>
                        </div>
                    </label>

                    <label class="relative flex items-start p-4 rounded-2xl border cursor-pointer transition hover:bg-slate-50 @if(old('visibility', $deck->visibility->value) === 'public') border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600 @else border-slate-200 @endif">
                        <div class="flex items-center h-5">
                            <input type="radio"
                                   name="visibility"
                                   value="public"
                                   @checked(old('visibility', $deck->visibility->value) === 'public')
                                   class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="font-semibold text-slate-900 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Công khai (Public)
                            </span>
                            <span class="block text-xs text-slate-500 mt-0.5">
                                Cho phép cộng đồng tìm kiếm và xem bộ từ.
                            </span>
                        </div>
                    </label>
                </div>
                @error('visibility')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nút hành động -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('decks.show', $deck) }}"
                   class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Hủy bỏ
                </a>
                <button type="submit"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30 cursor-pointer">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

