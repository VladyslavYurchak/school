<?php

use App\Http\Controllers\Admin\Testing\OptionController;
use App\Http\Controllers\Admin\Testing\QuestionController;
use App\Http\Controllers\Admin\Testing\ResultRangeController;
use App\Http\Controllers\Admin\Testing\SectionController;
use App\Http\Controllers\Admin\Testing\SessionController;
use App\Http\Controllers\Admin\Testing\TestController;
use App\Http\Controllers\Testing\PublicTestingController;
use Illuminate\Support\Facades\Route;

Route::prefix('testing')->name('testing.')->group(function () {
    Route::post('/start/{language}', [PublicTestingController::class, 'start'])->name('start');
    Route::get('/session/{session}', [PublicTestingController::class, 'show'])->name('session.show');
    Route::post('/session/{session}/submit', [PublicTestingController::class, 'submit'])->name('session.submit');
    Route::get('/session/{session}/result', [PublicTestingController::class, 'result'])->name('session.result');
    Route::post('/session/{session}/lead', [PublicTestingController::class, 'storeLead'])->name('session.lead.store');
});

Route::prefix('admin/testing')
    ->name('admin.testing.')
    ->middleware(['admin'])
    ->group(function () {
        Route::resource('tests', TestController::class);
        Route::resource('tests.sections', SectionController::class)->shallow();
        Route::resource('tests.questions', QuestionController::class)->shallow();
        Route::resource('questions.options', OptionController::class)->shallow();
        Route::resource('tests.result-ranges', ResultRangeController::class)->shallow();

        Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
    });
