<?php

use App\Http\Controllers\Admin\Event\CreateController as EventCreateController;
use App\Http\Controllers\Admin\Event\DeleteController as EventDeleteController;
use App\Http\Controllers\Admin\Event\EditController as EventEditController;
use App\Http\Controllers\Admin\Event\IndexController as EventIndexController;
use App\Http\Controllers\Admin\Event\ShowController as EventShowController;
use App\Http\Controllers\Admin\Event\StoreController as EventStoreController;
use App\Http\Controllers\Admin\Event\UpdateController as EventUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/event', EventIndexController::class)->name('admin.event.index');
    Route::get('/event/create', EventCreateController::class)->name('admin.event.create');
    Route::post('/event', EventStoreController::class)->name('admin.event.store');
    Route::get('/event/{event}', EventShowController::class)->name('admin.event.show');
    Route::get('/event/{event}/edit', EventEditController::class)->name('admin.event.edit');
    Route::patch('/event/{event}', EventUpdateController::class)->name('admin.event.update');
    Route::delete('/event/{event}', EventDeleteController::class)->name('admin.event.delete');
});
