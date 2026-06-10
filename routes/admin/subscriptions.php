<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['admin'])->group(function () {
    Route::prefix('/subscription-templates')->name('admin.subscription-templates.')->group(function () {
        Route::get('/create', \App\Http\Controllers\Admin\SubscriptionTemplate\CreateController::class)->name('create');
        Route::post('/', \App\Http\Controllers\Admin\SubscriptionTemplate\StoreController::class)->name('store');
        Route::get('/', \App\Http\Controllers\Admin\SubscriptionTemplate\IndexController::class)->name('index');
        Route::get('/{subscriptionTemplate}/edit', \App\Http\Controllers\Admin\SubscriptionTemplate\EditController::class)->name('edit');
        Route::put('/{subscriptionTemplate}', \App\Http\Controllers\Admin\SubscriptionTemplate\UpdateController::class)->name('update');
        Route::delete('/{subscriptionTemplate}', \App\Http\Controllers\Admin\SubscriptionTemplate\DestroyController::class)->name('destroy');
    });
});
