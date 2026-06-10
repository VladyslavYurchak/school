<?php

use App\Http\Controllers\Admin\Photo\DeleteController;
use App\Http\Controllers\Admin\Photo\IndexController as PhotoIndexController;
use App\Http\Controllers\Admin\Photo\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::prefix('photos')->group(function () {
        Route::get('/', PhotoIndexController::class)->name('admin.photos.index');
        Route::post('/upload', UploadController::class)->name('admin.photos.upload');
        Route::delete('/delete/{photo}', DeleteController::class)->name('admin.photos.delete');
    });
});
