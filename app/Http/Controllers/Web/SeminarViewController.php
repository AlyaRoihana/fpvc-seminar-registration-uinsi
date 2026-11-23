<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Seminar;
use Illuminate\Contracts\View\View;

class SeminarViewController extends Controller
{
    /**
     * Tampilkan daftar seminar untuk mahasiswa.
     */
    public function index(): View
    {
        $seminars = Seminar::orderBy('seminar_date')->get();

        return view('seminars.index', compact('seminars'));
    }
}
