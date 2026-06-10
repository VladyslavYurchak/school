<?php

use App\Http\Controllers\Admin\StoreController as AdminIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
    Route::get('/main', AdminIndexController::class)->name('admin.index');
});
