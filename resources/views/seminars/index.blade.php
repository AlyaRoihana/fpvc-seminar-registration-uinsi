@extends('layouts.app')

@section('title', 'Daftar Seminar')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Seminar Tersedia</h1>
        <p class="mt-2 text-gray-600">Pilih seminar lalu daftar untuk mendapatkan e-ticket ber-QR.</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($seminars as $seminar)
            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-gray-900">{{ $seminar->title }}</h2>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold
                        {{ $seminar->remaining_seats > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                        {{ $seminar->remaining_seats }} kursi
                    </span>
                </div>

                <p class="mt-1 text-sm text-gray-500">{{ $seminar->speaker ?: 'Narasumber belum diumumkan' }}</p>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Tanggal</dt>
                        <dd class="font-medium">{{ $seminar->seminar_date->format('d M Y, H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Lokasi</dt>
                        <dd class="text-right font-medium">{{ $seminar->location }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Kapasitas</dt>
                        <dd class="font-medium">{{ $seminar->registered_count }} / {{ $seminar->capacity }}</dd>
                    </div>
                </dl>

                <div class="mt-6">
                    @guest
                        <a href="{{ route('login') }}" title="Login untuk mendaftar"
                           class="block w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-800">
                            Daftar Seminar
                        </a>
                    @else
                        @if (Auth::user()->role === 'mahasiswa' && $seminar->remaining_seats > 0 && $seminar->seminar_date->isFuture())
                            <form method="POST" action="{{ route('seminars.register', $seminar) }}" onsubmit="disableRegisterButton(this)">
                                @csrf
                                <button type="submit"
                                        class="register-submit w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-70">
                                    <span class="register-label">Daftar Seminar</span>
                                </button>
                            </form>
                        @else
                            <button type="button" disabled
                                    class="w-full cursor-not-allowed rounded-lg bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-500">
                                @if (Auth::user()->role !== 'mahasiswa')
                                    Khusus Mahasiswa
                                @elseif ($seminar->seminar_date->isPast())
                                    Seminar Selesai
                                @else
                                    Kuota Habis
                                @endif
                            </button>
                        @endif
                    @endguest
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-gray-500">Belum ada seminar yang tersedia.</p>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
    <script>
        // Guard UI terhadap double-submit. Pengaman utama tetap di database
        // (UNIQUE + UPDATE atomik) — ini hanya mencegah klik berulang.
        function disableRegisterButton(form) {
            const button = form.querySelector('.register-submit');
            if (!button || button.disabled) {
                return;
            }

            button.disabled = true;
            button.querySelector('.register-label').textContent = 'Memproses...';
        }
    </script>
@endpush
