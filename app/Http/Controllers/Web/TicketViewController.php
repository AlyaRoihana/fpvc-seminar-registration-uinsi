<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\View\View;

class TicketViewController extends Controller
{
    /**
     * Tampilkan e-ticket beserta QR untuk satu kode tiket.
     */
    public function show(string $ticketCode): View
    {
        $registration = Registration::with(['seminar', 'user'])
            ->where('ticket_code', $ticketCode)
            ->firstOrFail();

        $qrCode = (new Builder(data: $registration->ticket_code, size: 300, margin: 10))
            ->build(new SvgWriter())
            ->getString();

        return view('tickets.show', compact('registration', 'qrCode'));
    }
}
