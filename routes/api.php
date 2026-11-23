<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\SeminarController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/seminars', [SeminarController::class, 'index']);
    Route::get('/seminars/{seminar}', [SeminarController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:mahasiswa'])->group(function () {
    Route::post('/seminars/{seminar}/register', [RegistrationController::class, 'store']);
    Route::get('/tickets', [TicketController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:panitia'])->group(function () {
    Route::post('/seminars', [SeminarController::class, 'store']);
    Route::post('/tickets/checkin', [TicketController::class, 'checkin']);
});
