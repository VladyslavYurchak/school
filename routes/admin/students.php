<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::prefix('students')->name('admin.students.')->group(function () {
        Route::get('/main', \App\Http\Controllers\Admin\Students\IndexController::class)->name('index');
        Route::redirect('/create', '/admin/students/main')->name('create');
        Route::post('/store', \App\Http\Controllers\Admin\Students\StoreController::class)->name('store');
        Route::get('/{student}/edit', \App\Http\Controllers\Admin\Students\EditController::class)->name('edit');
        Route::put('/{student}', \App\Http\Controllers\Admin\Students\UpdateController::class)->name('update');
        Route::delete('/{student}', \App\Http\Controllers\Admin\Students\DestroyController::class)->name('destroy');
        Route::post('/{student}/subscription', \App\Http\Controllers\Admin\Students\Subscription\StoreController::class)
            ->name('subscriptions.store');
        Route::delete('/{student}/subscriptions/{month}', \App\Http\Controllers\Admin\Students\Subscription\DestroyController::class)
            ->where('month', '\d{4}-(0[1-9]|1[0-2])')
            ->name('subscriptions.destroyMonth');
        Route::put('/{student}/subscriptions/{month}/move', \App\Http\Controllers\Admin\Students\Subscription\MoveController::class)
            ->where('month', '\d{4}-(0[1-9]|1[0-2])')
            ->name('subscriptions.moveMonth');
        Route::get('/{student}/single-payments', \App\Http\Controllers\Admin\Students\Subscription\Single\IndexController::class)
            ->name('subscriptions.single.index');
        Route::delete('/{student}/single-payments/{payment}', \App\Http\Controllers\Admin\Students\Subscription\Single\DestroyController::class)
            ->name('subscriptions.single.destroy');
    });
});
