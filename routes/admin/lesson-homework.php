<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('lessons/{lesson}/homework-block/create', \App\Http\Controllers\Admin\Course\Lesson\Homework\CreateController::class)->name('admin.course.lesson.homework.create');
    Route::post('lessons/{lesson}/homework-block', \App\Http\Controllers\Admin\Course\Lesson\Homework\StoreController::class)->name('admin.course.lesson.homework.store');
    Route::put('lessons/{lesson}/homework-block', \App\Http\Controllers\Admin\Course\Lesson\Homework\UpdateController::class)
        ->name('admin.course.lesson.homework.update');
    Route::get('lessons/{lesson}/homework-block/edit', \App\Http\Controllers\Admin\Course\Lesson\Homework\EditController::class)
        ->name('admin.course.lesson.homework.edit');
    Route::delete('lessons/{lesson}/homework-block', \App\Http\Controllers\Admin\Course\Lesson\Homework\DestroyController::class)
        ->name('admin.course.lesson.homework.destroy');
    Route::delete('lessons/{lesson}/homework-file/{filename}', \App\Http\Controllers\Admin\Course\Lesson\Homework\DeleteFileController::class)
        ->name('admin.course.lesson.homework.file.delete')
        ->where('filename', '.*');
});
