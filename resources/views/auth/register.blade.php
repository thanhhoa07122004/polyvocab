@extends('layouts.app')

@section('title', 'Đăng ký tài khoản | ' . config('app.name'))

@section('content')
<div class="mx-auto max-w-md py-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tạo tài khoản mới</h1>
            <p class="mt-2 text-sm text-slate-600">
                Bắt đầu hành trình chinh phục từ vựng đa ngôn ngữ cùng PolyVocab
            </p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Họ và tên</label>
                <div class="mt-1">
                    <input id="name" name="name" type="text" autocomplete="name" required autofocus
                           value="{{ old('name') }}"
                           class="w-full rounded-xl border @error('name') border-rose-300 ring-rose-500 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20"
                           placeholder="Nguyễn Văn A">
                </div>
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Địa chỉ email</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required
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
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                           class="w-full rounded-xl border @error('password') border-rose-300 ring-rose-500 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20"
                           placeholder="Tối thiểu 8 ký tự">
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Xác nhận mật khẩu</label>
                <div class="mt-1">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                           class="w-full rounded-xl border @error('password_confirmation') border-rose-300 ring-rose-500 @else border-slate-300 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20"
                           placeholder="Nhập lại mật khẩu vừa nhập">
                </div>
                @error('password_confirmation')
                    <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-600/30 cursor-pointer">
                    Đăng ký tài khoản
                </button>
            </div>
        </form>

        <div class="mt-6 text-center border-t border-slate-100 pt-6">
            <p class="text-sm text-slate-600">
                Đã có tài khoản?
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition">
                    Đăng nhập ngay
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
