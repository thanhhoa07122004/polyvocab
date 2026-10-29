@extends('layouts.app')

@section('title', 'Quản lý ngôn ngữ | ' . config('app.name'))

@section('content')
<div class="space-y-8">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                        Quản trị hệ thống
                    </span>
                    <span class="text-xs text-slate-400">&bull;</span>
                    <span class="text-xs font-medium text-slate-500">Danh mục ngôn ngữ</span>
                </div>
                <h1 class="mt-2 text-2xl md:text-3xl font-bold tracking-tight text-slate-900">
                    Quản lý ngôn ngữ học
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Quản lý danh sách các ngôn ngữ được hỗ trợ cho người dùng tạo và học bộ từ.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.dashboard') }}"
                   class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    &larr; Tổng quan quản trị
                </a>
                <a href="{{ route('admin.languages.create') }}"
                   class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Thêm ngôn ngữ mới
                </a>
            </div>
        </div>

        <!-- Bộ lọc và tìm kiếm -->
        <div class="mt-6 border-t border-slate-100 pt-6">
            <form method="GET" action="{{ route('admin.languages.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Tìm kiếm theo mã (code), tên tiếng Việt hoặc tên bản địa..."
                           class="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition">
                        Tìm kiếm
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('admin.languages.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                            Xóa lọc
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách ngôn ngữ -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-700 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 rounded-l-xl">Mã (BCP 47)</th>
                        <th class="px-4 py-3">Tên tiếng Việt</th>
                        <th class="px-4 py-3">Tên bản địa</th>
                        <th class="px-4 py-3">Hướng chữ</th>
                        <th class="px-4 py-3">Thứ tự</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right rounded-r-xl">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($languages as $language)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-4 py-3 font-mono font-bold text-xs text-indigo-600">
                                {{ $language->code }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $language->name }}
                            </td>
                            <td class="px-4 py-3 text-slate-700" dir="{{ $language->direction }}">
                                {{ $language->native_name }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-700">
                                    {{ strtoupper($language->direction) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                {{ $language->sort_order }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($language->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Đang hoạt động
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                                        Đã tắt
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center space-x-2">
                                    <a href="{{ route('admin.languages.edit', $language) }}"
                                       class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                                        Sửa
                                    </a>
                                    <form method="POST" action="{{ route('admin.languages.toggle', $language) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="rounded-lg border px-2.5 py-1 text-xs font-medium transition cursor-pointer @if($language->is_active) border-rose-200 bg-white text-rose-600 hover:bg-rose-50 @else border-emerald-200 bg-white text-emerald-600 hover:bg-emerald-50 @endif">
                                            {{ $language->is_active ? 'Tắt' : 'Kích hoạt' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">
                                <div class="mx-auto max-w-sm">
                                    <svg class="mx-auto h-10 w-10 text-slate-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                    </svg>
                                    @if ($search !== '')
                                        <p class="font-medium text-slate-800">Không tìm thấy ngôn ngữ nào</p>
                                        <p class="mt-1 text-xs text-slate-500">Không có kết quả khớp với từ khóa "{{ $search }}".</p>
                                    @else
                                        <p class="font-medium text-slate-800">Chưa có ngôn ngữ nào trong hệ thống</p>
                                        <p class="mt-1 text-xs text-slate-500">Hãy thêm ngôn ngữ đầu tiên hoặc chạy seeder dữ liệu mẫu.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($languages->hasPages())
            <div class="mt-6 border-t border-slate-100 pt-6">
                {{ $languages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

