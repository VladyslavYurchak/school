<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('lessons/{lesson}/main-block/create', \App\Http\Controllers\Admin\Course\Lesson\Main\CreateController::class)->name('admin.course.lesson.main.create');
    Route::post('lessons/{lesson}/main-block', \App\Http\Controllers\Admin\Course\Lesson\Main\StoreController::class)->name('admin.course.lesson.main.store');
    Route::get('lessons/{lesson}/main-block/edit', \App\Http\Controllers\Admin\Course\Lesson\Main\CreateController::class)->name('admin.course.lesson.main.edit');
    Route::put('lessons/{lesson}/main-block/', \App\Http\Controllers\Admin\Course\Lesson\Main\UpdateController::class)->name('admin.course.lesson.main.update');
    Route::delete('lessons/{lesson}/main-block/audio', \App\Http\Controllers\Admin\Course\Lesson\Main\DeleteAudioController::class)
        ->name('admin.course.lesson.main.audio.delete');
    Route::delete('lessons/{lesson}/main-block/{filename}', \App\Http\Controllers\Admin\Course\Lesson\Main\DeleteFileController::class)
        ->name('admin.course.lesson.main.file.delete')
        ->where('filename', '.*');
    Route::delete('lesson/{lesson}/main', \App\Http\Controllers\Admin\Course\Lesson\Main\DestroyController::class)
        ->name('admin.course.lesson.main.destroy');
});
