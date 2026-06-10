<?php

use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/student/dashboard', StudentDashboardController::class)
        ->name('student.dashboard');

    Route::get('/student/payments', [StudentPaymentController::class, 'index'])
        ->name('student.payments.index');

    Route::post('/student/payments', [StudentPaymentController::class, 'store'])
        ->name('student.payments.store');

    Route::get('/student/payments/monopay/{payment}', [StudentPaymentController::class, 'checkout'])
        ->name('student.payments.checkout');

    Route::get('/student/payments/result', [StudentPaymentController::class, 'result'])
        ->name('student.payments.result');
});