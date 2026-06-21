<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('courses/{course}/lessons/create', \App\Http\Controllers\Admin\Course\Lesson\CreateController::class)->name('admin.course.lesson.create');
    Route::post('courses/{course}/lessons', \App\Http\Controllers\Admin\Course\Lesson\StoreController::class)->name('admin.course.lesson.store');
    Route::get('/lesson/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\ShowController::class)->name('admin.course.lesson.show');
    Route::delete('lessons/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\DeleteController::class)->name('admin.course.lesson.delete');
    Route::post('courses/{course}/lessons/update-order', \App\Http\Controllers\Admin\Course\Lesson\UpdateLessonOrderController::class)->name('admin.course.lesson.updateOrder');
    Route::get('lessons/{lesson}/edit', \App\Http\Controllers\Admin\Course\Lesson\EditController::class)
        ->name('admin.course.lesson.edit');
    Route::put('lessons/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\UpdateController::class)
        ->name('admin.course.lesson.update');
});
