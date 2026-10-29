<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center px-6 py-4">
            <a href="{{ route('home') }}" class="text-xl font-bold text-indigo-600">
                {{ config('app.name') }}
            </a>
        </nav>
    </header>
    <main class="mx-auto max-w-6xl px-6 py-12">
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>
