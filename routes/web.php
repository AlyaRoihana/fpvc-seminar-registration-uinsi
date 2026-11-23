<?php

use App\Http\Controllers\Web\AuthViewController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\RegistrationController;
use App\Http\Controllers\Web\SeminarViewController;
use App\Http\Controllers\Web\TicketViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SeminarViewController::class, 'index'])->name('seminars.index');
Route::get('/tickets/{ticket_code}', [TicketViewController::class, 'show'])->name('tickets.show');

Route::get('/login', [AuthViewController::class, 'show'])->name('login');
Route::post('/login', [AuthViewController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthViewController::class, 'logout'])->name('logout');

    Route::middleware('role:panitia')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    Route::middleware('role:mahasiswa')->group(function () {
        Route::post('/seminars/{seminar}/register', [RegistrationController::class, 'store'])
            ->name('seminars.register');
    });
});
