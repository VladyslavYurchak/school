<?php

use App\Http\Controllers\Admin\StoreController as AdminIndexController;
use App\Http\Controllers\Telegram\DisconnectTelegramController;
use App\Http\Controllers\Telegram\StartTelegramLinkController;
use App\Http\Controllers\Teacher\SettingsController;
use App\Http\Controllers\Teacher\UpdateSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
    Route::get('/main', AdminIndexController::class)->name('admin.index');

    Route::get('/teacher/settings', SettingsController::class)
        ->name('teacher.settings.edit');
    Route::patch('/teacher/settings', UpdateSettingsController::class)
        ->name('teacher.settings.update');

    Route::post('/teacher/telegram/connect', StartTelegramLinkController::class)
        ->name('teacher.telegram.connect');

    Route::delete('/teacher/telegram', DisconnectTelegramController::class)
        ->name('teacher.telegram.disconnect');
});
