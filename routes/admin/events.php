<?php

use App\Http\Controllers\Admin\Event\CreateController as EventCreateController;
use App\Http\Controllers\Admin\Event\DeleteController as EventDeleteController;
use App\Http\Controllers\Admin\Event\IndexController as EventIndexController;
use App\Http\Controllers\Admin\Event\StoreController as EventStoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/event', EventIndexController::class)->name('admin.event.index');
    Route::get('/event/create', EventCreateController::class)->name('admin.event.create');
    Route::post('/event', EventStoreController::class)->name('admin.event.store');
    Route::delete('/event/{event}', EventDeleteController::class)->name('admin.event.delete');
});
