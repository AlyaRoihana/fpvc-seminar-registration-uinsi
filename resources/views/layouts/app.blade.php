<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Seminar UINSI')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
    <nav class="bg-emerald-700 text-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('seminars.index') }}" class="font-bold text-lg">Seminar UINSI</a>
            <div class="flex items-center gap-4">
                @auth
                    @if (Auth::user()->role === 'panitia')
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium hover:bg-white/20">
                            Dasbor
                        </a>
                    @endif
                    <span class="hidden text-sm text-emerald-100 sm:inline">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium hover:bg-white/20">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium hover:bg-white/20">
                        Login
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
