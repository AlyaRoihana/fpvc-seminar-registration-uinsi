<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Seminar;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan sisa kursi dan jumlah check-in untuk panitia.
     */
    public function index(): View
    {
        $seminars = Seminar::withCount([
            'registrations as checked_in_count' => fn ($query) => $query->whereNotNull('checked_in_at'),
        ])->orderBy('seminar_date')->get();

        return view('dashboard.index', compact('seminars'));
    }
}
