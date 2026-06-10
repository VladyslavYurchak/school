<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::prefix('groups')->name('admin.groups.')->group(function () {
        Route::get('/', \App\Http\Controllers\Admin\Groups\IndexController::class)->name('index');
        Route::get('/create', \App\Http\Controllers\Admin\Groups\CreateController::class)->name('create');
        Route::post('/', \App\Http\Controllers\Admin\Groups\StoreController::class)->name('store');
        Route::get('/{group}/edit', \App\Http\Controllers\Admin\Groups\EditController::class)->name('edit');
        Route::put('/{group}', \App\Http\Controllers\Admin\Groups\UpdateController::class)->name('update');
        Route::delete('/{group}', \App\Http\Controllers\Admin\Groups\DestroyController::class)->name('destroy');
        Route::post('/{group}/add-student', \App\Http\Controllers\Admin\Groups\AddStudentToGroupController::class)
            ->name('add-student');
        Route::delete('/{group}/remove-student/{student}', \App\Http\Controllers\Admin\Groups\RemoveStudentFromGroupController::class)
            ->name('remove-student');
    });
});
