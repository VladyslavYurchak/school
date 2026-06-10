<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::prefix('teachers')->name('admin.teachers.')->group(function () {
        Route::get('/', \App\Http\Controllers\Admin\Teachers\IndexController::class)->name('index');
        Route::get('/create', \App\Http\Controllers\Admin\Teachers\CreateController::class)->name('create');
        Route::post('/', \App\Http\Controllers\Admin\Teachers\StoreController::class)->name('store');
        Route::get('/{teacher}/edit', \App\Http\Controllers\Admin\Teachers\EditController::class)->name('edit');
        Route::put('/{teacher}', \App\Http\Controllers\Admin\Teachers\UpdateController::class)->name('update');
        Route::delete('/{teacher}', \App\Http\Controllers\Admin\Teachers\DestroyController::class)->name('destroy');
    });
});
