<?php

use App\Http\Controllers\Admin\History\HistoryActionsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/history-actions', HistoryActionsController::class)
        ->name('admin.history_actions.index');
});
