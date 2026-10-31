@extends('layouts.app')

@section('title', 'Thêm ngôn ngữ mới | ' . config('app.name'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.languages.index') }}" class="hover:text-indigo-600">Quản lý ngôn ngữ</a>
                <span>&rsaquo;</span>
                <span>Thêm mới</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Thêm ngôn ngữ mới
            </h1>
        </div>
        <a href="{{ route('admin.languages.index') }}"
           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Quay lại danh sách
        </a>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.languages.store') }}" class="space-y-6">
            @csrf

            <!-- Mã ngôn ngữ (Code) -->
            <div>
                <label for="code" class="block text-sm font-medium text-slate-900">
                    Mã ngôn ngữ (Chuẩn BCP 47) <span class="text-rose-500">*</span>
                </label>
                <div class="mt-1">
                    <input type="text"
                           name="code"
                           id="code"
                           value="{{ old('code') }}"
                           placeholder="Ví dụ: vi, en, ja, ko, zh-Hans, pt-BR, ar"
                           required
                           class="w-full rounded-xl border @error('code') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Mã định danh theo chuẩn BCP 47. Không thể thay đổi sau khi đã lưu.
                </p>
                @error('code')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tên tiếng Việt -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-900">
                    Tên tiếng Việt <span class="text-rose-500">*</span>
                </label>
                <div class="mt-1">
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name') }}"
                           placeholder="Ví dụ: Tiếng Nhật, Tiếng Ả Rập..."
                           required
                           class="w-full rounded-xl border @error('name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tên bản địa -->
            <div>
                <label for="native_name" class="block text-sm font-medium text-slate-900">
                    Tên bản địa (Native Name) <span class="text-rose-500">*</span>
                </label>
                <div class="mt-1">
                    <input type="text"
                           name="native_name"
                           id="native_name"
                           value="{{ old('native_name') }}"
                           placeholder="Ví dụ: 日本語, English, العربية..."
                           required
                           class="w-full rounded-xl border @error('native_name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                @error('native_name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Hướng chữ (Direction) -->
                <div>
                    <label for="direction" class="block text-sm font-medium text-slate-900">
                        Hướng chữ hiển thị <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-1">
                        <select name="direction"
                                id="direction"
                                required
                                class="w-full rounded-xl border @error('direction') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option value="ltr" @selected(old('direction', 'ltr') === 'ltr')>LTR (Trái sang phải - Mặc định)</option>
                            <option value="rtl" @selected(old('direction') === 'rtl')>RTL (Phải sang trái - Ví dụ: Ả Rập, Do Thái)</option>
                        </select>
                    </div>
                    @error('direction')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Thứ tự sắp xếp -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-slate-900">
                        Thứ tự sắp xếp
                    </label>
                    <div class="mt-1">
                        <input type="number"
                               name="sort_order"
                               id="sort_order"
                               value="{{ old('sort_order', 0) }}"
                               min="0"
                               class="w-full rounded-xl border @error('sort_order') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Số nhỏ hơn sẽ ưu tiên hiển thị trước.</p>
                    @error('sort_order')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Cấu hình Azure Speech TTS -->
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-900">Cấu hình Azure Speech TTS</span>
                    <span class="text-2xs text-indigo-600">Dùng cho phát âm từ vựng và bài tập nghe</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="tts_locale" class="block text-xs font-medium text-slate-700">
                            Azure Locale (BCP-47)
                        </label>
                        <div class="mt-1">
                            <input type="text"
                                   name="tts_locale"
                                   id="tts_locale"
                                   value="{{ old('tts_locale') }}"
                                   placeholder="Ví dụ: vi-VN, en-US, ja-JP..."
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>

                    <div>
                        <label for="tts_voice" class="block text-xs font-medium text-slate-700">
                            Tên giọng đọc Azure (Voice Name)
                        </label>
                        <div class="mt-1">
                            <input type="text"
                                   name="tts_voice"
                                   id="tts_voice"
                                   value="{{ old('tts_voice') }}"
                                   placeholder="Ví dụ: vi-VN-HoaiMyNeural, en-US-JennyNeural..."
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trạng thái hoạt động -->
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           @checked(old('is_active', true))
                           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
                    <span class="ml-3 block">
                        <span class="text-sm font-semibold text-slate-900">Cho phép sử dụng ngay (Hoạt động)</span>
                        <span class="block text-xs text-slate-500">
                            Nếu bỏ chọn, ngôn ngữ này sẽ tạm ẩn khỏi danh sách lựa chọn tạo bộ từ mới của thành viên.
                        </span>
                    </span>
                </label>
            </div>

            <!-- Nút hành động -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.languages.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Hủy bỏ
                </a>
                <button type="submit"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30 cursor-pointer">
                    Lưu ngôn ngữ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

