<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Tampilkan e-ticket milik mahasiswa yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = Registration::with('seminar:id,title,seminar_date,location,capacity,registered_count')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Registration $registration) => [
                'id' => $registration->id,
                'seminar' => $registration->seminar,
                'ticket_code' => $registration->ticket_code,
                'ticket_url' => route('tickets.show', $registration->ticket_code),
                'checked_in_at' => $registration->checked_in_at,
                'created_at' => $registration->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Check-in e-ticket sekali pakai (khusus panitia).
     */
    public function checkin(CheckInRequest $request): JsonResponse
    {
        $ticketCode = $request->validated('ticket_code');

        // Conditional UPDATE: hanya tiket yang belum check-in yang berubah.
        $affected = DB::table('registrations')
            ->where('ticket_code', $ticketCode)
            ->whereNull('checked_in_at')
            ->update(['checked_in_at' => now()]);

        if ($affected === 0) {
            return $this->checkinFailure($ticketCode);
        }

        $registration = Registration::with(['seminar:id,title,capacity,registered_count', 'user:id,name'])
            ->where('ticket_code', $ticketCode)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil.',
            'data' => $registration,
        ]);
    }

    /**
     * Bedakan tiket tidak ditemukan dengan tiket yang sudah dipakai.
     */
    private function checkinFailure(string $ticketCode): JsonResponse
    {
        $exists = Registration::where('ticket_code', $ticketCode)->exists();

        return response()->json([
            'success' => false,
            'message' => $exists
                ? 'E-ticket sudah digunakan.'
                : 'E-ticket tidak ditemukan.',
            'code' => $exists ? 'TICKET_ALREADY_USED' : 'TICKET_NOT_FOUND',
        ], $exists ? 409 : 404);
    }
}
