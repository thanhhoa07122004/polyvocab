@extends('layouts.app')

@section('title', 'Đăng nhập | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-md py-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Đăng nhập</h1>
            <p class="mt-2 text-sm text-slate-600">
                Chào mừng bạn quay trở lại với PolyVocab
            </p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Địa chỉ email</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required autofocus
                           value="{{ old('email') }}"
                           class="w-full rounded-xl border @error('email') border-rose-300 ring-rose-500 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20"
                           placeholder="name@example.com">
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Mật khẩu</label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="w-full rounded-xl border @error('password') border-rose-300 ring-rose-500 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20"
                           placeholder="Nhập mật khẩu của bạn">
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center space-x-2 text-sm text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" value="1"
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30 cursor-pointer">
                    Đăng nhập
                </button>
            </div>
        </form>

        <div class="mt-6 text-center border-t border-slate-100 pt-6">
            <p class="text-sm text-slate-600">
                Chưa có tài khoản?
                <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition">
                    Đăng ký ngay
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
