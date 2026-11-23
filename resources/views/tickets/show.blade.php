@extends('layouts.app')

@section('title', 'E-Ticket Seminar')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('seminars.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">
                &larr; Kembali ke daftar seminar
            </a>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="bg-emerald-700 px-6 py-5 text-white">
                <p class="text-xs uppercase tracking-widest text-emerald-100">E-Ticket Seminar UINSI</p>
                <h1 class="mt-1 text-2xl font-bold">{{ $registration->seminar->title }}</h1>
                <p class="mt-1 text-sm text-emerald-100">{{ $registration->seminar->location }}</p>
            </div>

            <div class="flex flex-col items-center gap-8 px-6 py-8 sm:flex-row sm:items-start">
                <div class="shrink-0 rounded-xl border border-gray-200 bg-white p-3">
                    {!! $qrCode !!}
                </div>

                <div class="w-full space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pemilik tiket</p>
                        <p class="mt-1 text-lg font-semibold">{{ $registration->user->name }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kode tiket</p>
                        <p class="mt-1 break-all font-mono text-sm">{{ $registration->ticket_code }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status check-in</p>
                        @if ($registration->checked_in_at)
                            <span class="mt-1 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800">
                                Sudah check-in · {{ $registration->checked_in_at->format('d M Y, H:i') }}
                            </span>
                        @else
                            <span class="mt-1 inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">
                                Belum check-in
                            </span>
                        @endif
                    </div>

                    <p class="rounded-lg bg-gray-50 p-3 text-xs text-gray-600">
                        Tunjukkan QR ini ke panitia di pintu masuk aula. E-ticket hanya bisa dipakai sekali.
                    </p>
                </div>
            </div>
        </section>
    </div>
@endsection
