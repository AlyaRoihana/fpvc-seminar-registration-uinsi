@extends('layouts.app')

@section('title', 'Dasbor Panitia')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dasbor Panitia</h1>
        <p class="mt-2 text-gray-600">Pantau sisa kursi dan jumlah check-in setiap seminar.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Seminar</th>
                    <th class="px-6 py-3">Tanggal</th>
                    <th class="px-6 py-3">Terdaftar</th>
                    <th class="px-6 py-3">Sisa kursi</th>
                    <th class="px-6 py-3">Check-in</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($seminars as $seminar)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-semibold text-gray-900">{{ $seminar->title }}</p>
                            <p class="text-xs text-gray-500">{{ $seminar->location }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $seminar->seminar_date->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4">{{ $seminar->registered_count }} / {{ $seminar->capacity }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $seminar->remaining_seats > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $seminar->remaining_seats }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $seminar->checked_in_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada seminar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
