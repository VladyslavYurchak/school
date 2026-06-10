<?php

use App\Http\Controllers\MonoPayWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/monopay/webhook', MonoPayWebhookController::class)
    ->name('monopay.webhook');