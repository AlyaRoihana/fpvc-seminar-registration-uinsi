<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seminar;
use App\Services\SeminarRegistrationService;
use App\Support\RegistrationErrorMessages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RegistrationController extends Controller
{
    /**
     * Daftarkan mahasiswa ke seminar dan kembalikan e-ticket.
     */
    public function store(Request $request, Seminar $seminar): JsonResponse
    {
        try {
            $registration = app(SeminarRegistrationService::class)
                ->register($seminar, $request->user());
        } catch (RuntimeException $e) {
            $code = $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => RegistrationErrorMessages::for($code),
                'code' => $code,
            ], RegistrationErrorMessages::status($code));
        }

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil.',
            'data' => [
                'registration' => $registration,
                'ticket_url' => route('tickets.show', $registration->ticket_code),
            ],
        ], 201);
    }
}
