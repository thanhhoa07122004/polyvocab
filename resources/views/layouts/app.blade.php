<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
    <header class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-xs">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-6">
                <a href="{{ route('home') }}" class="flex items-center space-x-2 text-xl font-bold text-indigo-600 hover:text-indigo-700 transition">
                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                        <path d="M6 6h10"/>
                        <path d="M6 10h10"/>
                    </svg>
                    <span>{{ config('app.name') }}</span>
                </a>

                <a href="{{ route('home') }}" class="hidden sm:inline-block text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                    Trang chủ
                </a>
                <a href="{{ route('explore') }}" class="hidden sm:inline-block text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                    Khám phá
                </a>
                @auth
                    <a href="{{ route('decks.index') }}" class="hidden sm:inline-block text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                        Bộ từ của tôi
                    </a>
                    <a href="{{ route('dashboard') }}" class="hidden sm:inline-block text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                        Bảng điều khiển
                    </a>

                    @can('access-admin')
                        <a href="{{ route('admin.languages.index') }}" class="hidden sm:inline-block text-sm font-medium text-amber-700 hover:text-amber-800 transition">
                            Ngôn ngữ
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline-flex items-center space-x-1.5 text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-3 py-1.5 rounded-xl transition">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Quản trị</span>
                        </a>
                    @endcan
                @endauth
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('explore') }}" class="sm:hidden text-xs font-medium text-slate-600 hover:text-indigo-600">
                    Khám phá
                </a>
                @auth
                    <a href="{{ route('decks.index') }}" class="sm:hidden text-xs font-medium text-indigo-600 hover:text-indigo-700">
                        Bộ từ
                    </a>
                    @can('access-admin')
                        <a href="{{ route('admin.languages.index') }}" class="sm:hidden text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg">
                            Ngôn ngữ
                        </a>
                    @endcan

                    <div class="hidden md:flex items-center space-x-2 text-sm text-slate-700 bg-slate-100 rounded-full px-3 py-1">
                        <span class="w-2 h-2 rounded-full @if(auth()->user()->isAdmin()) bg-amber-500 @else bg-emerald-500 @endif"></span>
                        <span class="font-medium truncate max-w-[150px]">{{ auth()->user()->name }}</span>
                    </div>

                    <a href="{{ route('dashboard') }}" class="sm:hidden text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        Bảng điều khiển
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition focus:outline-none focus:ring-2 focus:ring-rose-500/20 cursor-pointer">
                            Đăng xuất
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 hover:text-indigo-600 transition">
                        Đăng nhập
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                        Đăng ký
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <div class="mx-auto w-full max-w-6xl px-6 pt-6">
        @if (session('success'))
            <div class="mb-6 flex items-center rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                <svg class="w-5 h-5 mr-3 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 flex items-center rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <svg class="w-5 h-5 mr-3 shrink-0 text-rose-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-6 flex items-center rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-sm text-indigo-800">
                <svg class="w-5 h-5 mr-3 shrink-0 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif
    </div>

    <main class="mx-auto w-full max-w-6xl px-6 pb-12 flex-1">
        @yield('content')
    </main>

    <footer class="mt-auto border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        <div class="mx-auto max-w-6xl px-6">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} - Hệ thống học từ vựng đa ngôn ngữ thông minh.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn && !btn.disabled) {
                        setTimeout(() => {
                            btn.disabled = true;
                            btn.classList.add('opacity-75', 'cursor-not-allowed');
                        }, 10);
                    }
                });
            });
        });
    </script>

    @livewireScripts
</body>
</html>
