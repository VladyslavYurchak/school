<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::post('/languages', \App\Http\Controllers\Admin\Language\StoreController::class)->name('admin.language.store');

    Route::get('/courses', \App\Http\Controllers\Admin\Course\IndexController::class)->name('admin.course.index');
    Route::get('/courses/create', \App\Http\Controllers\Admin\Course\CreateController::class)->name('admin.course.create');
    Route::post('/courses', \App\Http\Controllers\Admin\Course\StoreController::class)->name('admin.course.store');
    Route::get('/courses/{course}/edit', \App\Http\Controllers\Admin\Course\EditController::class)->name('admin.course.edit');
    Route::put('/courses/{course}', \App\Http\Controllers\Admin\Course\UpdateController::class)->name('admin.course.update');
    Route::delete('/courses/{course}', \App\Http\Controllers\Admin\Course\DeleteController::class)->name('admin.course.delete');
    Route::get('/courses/filter/{language}', \App\Http\Controllers\Admin\Course\FilterByLanguageController::class)->name('admin.course.filter');
    Route::get('/courses/{course}', \App\Http\Controllers\Admin\Course\ShowController::class)->name('admin.course.show');
    Route::post('/courses/{course}/publish', \App\Http\Controllers\Admin\Course\TogglePublishController::class)->name('admin.courses.publish');
});
