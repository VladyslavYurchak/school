<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/information', \App\Http\Controllers\Admin\Information\IndexController::class)
        ->name('admin.information.index');
});
