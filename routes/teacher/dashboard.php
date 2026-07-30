<?php

use App\Http\Controllers\Admin\StoreController as AdminIndexController;
use App\Http\Controllers\Telegram\DisconnectTelegramController;
use App\Http\Controllers\Telegram\StartTelegramLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
    Route::get('/main', AdminIndexController::class)->name('admin.index');

    Route::post('/teacher/telegram/connect', StartTelegramLinkController::class)
        ->name('teacher.telegram.connect');

    Route::delete('/teacher/telegram', DisconnectTelegramController::class)
        ->name('teacher.telegram.disconnect');
});
