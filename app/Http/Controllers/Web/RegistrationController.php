<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Seminar;
use App\Services\SeminarRegistrationService;
use App\Support\RegistrationErrorMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RegistrationController extends Controller
{
    /**
     * Daftarkan mahasiswa lalu arahkan ke halaman e-ticket.
     */
    public function store(Request $request, Seminar $seminar): RedirectResponse
    {
        try {
            $registration = app(SeminarRegistrationService::class)
                ->register($seminar, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', RegistrationErrorMessages::for($e->getMessage()));
        }

        return redirect()->route('tickets.show', $registration->ticket_code)
            ->with('success', 'Pendaftaran berhasil. E-ticket Anda siap.');
    }
}
