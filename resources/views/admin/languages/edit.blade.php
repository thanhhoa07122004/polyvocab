@extends('layouts.app')

@section('title', 'Chỉnh sửa ngôn ngữ: ' . $language->name . ' | ' . config('app.name'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.languages.index') }}" class="hover:text-indigo-600">Quản lý ngôn ngữ</a>
                <span>&rsaquo;</span>
                <span>Chỉnh sửa: {{ $language->name }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Chỉnh sửa ngôn ngữ
            </h1>
        </div>
        <a href="{{ route('admin.languages.index') }}"
           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Quay lại danh sách
        </a>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.languages.update', $language) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Mã ngôn ngữ (Code) - Không được sửa -->
            <div>
                <label class="block text-sm font-medium text-slate-900">
                    Mã ngôn ngữ (Chuẩn BCP 47)
                </label>
                <div class="mt-1">
                    <input type="text"
                           value="{{ $language->code }}"
                           disabled
                           class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm font-mono text-slate-600 cursor-not-allowed">
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Mã ngôn ngữ là định danh duy nhất và không được phép thay đổi để bảo toàn toàn vẹn dữ liệu.
                </p>
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
                           value="{{ old('name', $language->name) }}"
                           required
                           class="w-full rounded-xl border @error('name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
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
                           value="{{ old('native_name', $language->native_name) }}"
                           required
                           class="w-full rounded-xl border @error('native_name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
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
                            <option value="ltr" @selected(old('direction', $language->direction) === 'ltr')>LTR (Trái sang phải - Mặc định)</option>
                            <option value="rtl" @selected(old('direction', $language->direction) === 'rtl')>RTL (Phải sang trái)</option>
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
                               value="{{ old('sort_order', $language->sort_order) }}"
                               min="0"
                               class="w-full rounded-xl border @error('sort_order') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Số nhỏ hơn sẽ ưu tiên hiển thị trước.</p>
                    @error('sort_order')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Trạng thái hoạt động -->
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           @checked(old('is_active', $language->is_active))
                           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
                    <span class="ml-3 block">
                        <span class="text-sm font-semibold text-slate-900">Đang hoạt động (Cho phép sử dụng)</span>
                        <span class="block text-xs text-slate-500">
                            Nếu tắt, các bộ từ đã tạo trước đó vẫn sử dụng bình thường nhưng thành viên không thể chọn ngôn ngữ này khi tạo bộ từ mới.
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
                    Cập nhật ngôn ngữ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

