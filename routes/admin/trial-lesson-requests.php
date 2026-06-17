<?php

use App\Http\Controllers\Admin\TrialLessonRequests\MarkContactedController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
    Route::post('/trial-lesson-requests/{trialLessonRequest}/mark-contacted', MarkContactedController::class)
        ->name('trial-lesson-requests.mark-contacted');
});
