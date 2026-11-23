@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Login Mahasiswa</h1>

        <form method="POST" action="{{ route('login') }}" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-600 focus:ring-emerald-600">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-600 focus:ring-emerald-600">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                Login
            </button>
        </form>

        <p class="mt-4 rounded-lg bg-gray-100 p-3 text-xs text-gray-600">
            Akun uji: <strong>mahasiswa@uinsi.test</strong> / <strong>password</strong>
        </p>
    </div>
@endsection
