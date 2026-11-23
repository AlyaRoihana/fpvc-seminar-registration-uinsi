<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeminarRequest;
use App\Models\Seminar;
use Illuminate\Http\JsonResponse;

class SeminarController extends Controller
{
    /**
     * Tampilkan daftar seminar beserta sisa kursinya.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Seminar::orderBy('seminar_date')->get(),
        ]);
    }

    /**
     * Tampilkan detail satu seminar.
     */
    public function show(Seminar $seminar): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $seminar,
        ]);
    }

    /**
     * Buat seminar baru (khusus panitia).
     */
    public function store(StoreSeminarRequest $request): JsonResponse
    {
        $seminar = Seminar::create([
            'title' => $request->validated('title'),
            'speaker' => $request->validated('speaker'),
            'seminar_date' => $request->validated('seminar_date'),
            'location' => $request->validated('location'),
            'capacity' => $request->validated('capacity'),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seminar berhasil dibuat.',
            'data' => $seminar,
        ], 201);
    }
}
