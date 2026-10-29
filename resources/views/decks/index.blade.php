@extends('layouts.app')

@section('title', 'Bộ từ của tôi | ' . config('app.name'))

@section('content')
<div class="space-y-8">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    Không gian học tập cá nhân
                </span>
                <h1 class="mt-2 text-2xl md:text-3xl font-bold tracking-tight text-slate-900">
                    Bộ từ của tôi
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Quản lý danh sách các bộ từ vựng bạn đã tạo để phục vụ quá trình ghi nhớ và ôn tập.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('decks.create') }}"
                   class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tạo bộ từ mới
                </a>
            </div>
        </div>

        <!-- Thanh tìm kiếm và bộ lọc -->
        <div class="mt-6 border-t border-slate-100 pt-6">
            <form method="GET" action="{{ route('decks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Tìm kiếm tên hoặc mô tả -->
                <div class="relative sm:col-span-2">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Tìm theo tên bộ từ hoặc mô tả..."
                           class="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>

                <!-- Lọc theo ngôn ngữ -->
                <div>
                    <select name="language_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Tất cả ngôn ngữ</option>
                        @foreach ($languages as $lang)
                            <option value="{{ $lang->id }}" @selected($languageId == $lang->id)>
                                {{ $lang->name }} ({{ $lang->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Lọc theo trạng thái hiển thị -->
                <div class="flex gap-2">
                    <select name="visibility"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Tất cả trạng thái</option>
                        <option value="private" @selected($visibility === 'private')>Riêng tư</option>
                        <option value="public" @selected($visibility === 'public')>Công khai</option>
                    </select>

                    <button type="submit"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition shrink-0">
                        Lọc
                    </button>
                    @if ($search !== '' || $languageId || $visibility)
                        <a href="{{ route('decks.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition shrink-0"
                           title="Xóa bộ lọc">
                            Xóa
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách bộ từ -->
    @if ($decks->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($decks as $deck)
                <div class="group relative flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm hover:border-indigo-200 hover:shadow-md transition">
                    <div>
                        <!-- Header thẻ: Ngôn ngữ & Trạng thái -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center space-x-1.5 text-xs">
                                <span class="font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                                    {{ $deck->learningLanguage->name }}
                                </span>
                                <span class="text-slate-400">&rarr;</span>
                                <span class="font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-lg">
                                    {{ $deck->meaningLanguage->name }}
                                </span>
                            </div>

                            @if ($deck->isPublic())
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                    Công khai
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 border border-slate-200">
                                    Riêng tư
                                </span>
                            @endif
                        </div>

                        <!-- Tên bộ từ -->
                        <h2 class="text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition" dir="{{ $deck->learningLanguage->direction }}">
                            <a href="{{ route('decks.show', $deck) }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                {{ $deck->name }}
                            </a>
                        </h2>

                        <!-- Mô tả -->
                        @if ($deck->description)
                            <p class="mt-2 text-sm text-slate-600 line-clamp-2">
                                {{ $deck->description }}
                            </p>
                        @else
                            <p class="mt-2 text-xs italic text-slate-400">
                                Chưa có mô tả cho bộ từ này.
                            </p>
                        @endif
                    </div>

                    <!-- Footer thẻ: Thông tin ngày và thao tác -->
                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 relative z-10">
                        <span>{{ $deck->created_at ? $deck->created_at->format('d/m/Y') : '' }}</span>

                        <div class="flex items-center space-x-2">
                            <a href="{{ route('decks.edit', $deck) }}"
                               class="text-indigo-600 hover:text-indigo-800 font-medium px-2 py-1 rounded hover:bg-indigo-50 transition">
                                Sửa
                            </a>
                            <span class="text-slate-300">|</span>
                            <form method="POST" action="{{ route('decks.destroy', $deck) }}"
                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa bộ từ « {{ addslashes($deck->name) }} » không? Hành động này không thể hoàn tác.');"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-800 font-medium px-2 py-1 rounded hover:bg-rose-50 transition cursor-pointer">
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($decks->hasPages())
            <div class="mt-6">
                {{ $decks->links() }}
            </div>
        @endif
    @else
        <!-- Trạng thái danh sách rỗng -->
        <div class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-4">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>

            @if ($search !== '' || $languageId || $visibility)
                <h3 class="text-base font-bold text-slate-900">Không tìm thấy bộ từ nào</h3>
                <p class="mt-1 text-sm text-slate-500 max-w-md mx-auto">
                    Không có bộ từ nào khớp với tiêu chí tìm kiếm hoặc bộ lọc hiện tại của bạn.
                </p>
                <div class="mt-6">
                    <a href="{{ route('decks.index') }}"
                       class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Xóa tất cả bộ lọc
                    </a>
                </div>
            @else
                <h3 class="text-base font-bold text-slate-900">Bạn chưa tạo bộ từ nào</h3>
                <p class="mt-1 text-sm text-slate-500 max-w-md mx-auto">
                    Hãy tạo bộ từ vựng đầu tiên để bắt đầu hành trình học tập và ghi nhớ của bạn với PolyVocab!
                </p>
                <div class="mt-6">
                    <a href="{{ route('decks.create') }}"
                       class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tạo bộ từ đầu tiên ngay
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

