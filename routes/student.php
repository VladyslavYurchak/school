<?php

use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\StudentVocabularyController;
use App\Http\Controllers\Telegram\DisconnectTelegramController;
use App\Http\Controllers\Telegram\StartTelegramLinkController;
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

    Route::get('/student/vocabulary', [StudentVocabularyController::class, 'learn'])
        ->name('student.vocabulary.learn');

    Route::post('/student/vocabulary/{vocabularyItem}/progress', [StudentVocabularyController::class, 'updateProgress'])
        ->name('student.vocabulary.progress');

    Route::get('/student/vocabulary/review', [StudentVocabularyController::class, 'review'])
        ->name('student.vocabulary.review');

    Route::post('/student/vocabulary/review/{vocabularyItem}', [StudentVocabularyController::class, 'submitReview'])
        ->name('student.vocabulary.review.submit');

    Route::post('/student/telegram/connect', StartTelegramLinkController::class)
        ->name('student.telegram.connect');

    Route::delete('/student/telegram', DisconnectTelegramController::class)
        ->name('student.telegram.disconnect');
});
