<?php

use App\Http\Controllers\MonoPayWebhookController;
use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/monopay/webhook', MonoPayWebhookController::class)
    ->name('monopay.webhook');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->name('telegram.webhook');
