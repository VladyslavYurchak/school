<?php

use App\Http\Controllers\Admin\Course\Lesson\ExerciseController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('lessons/{lesson}/exercises', [ExerciseController::class, 'index'])
        ->name('admin.course.lesson.exercises.index');
    Route::get('lessons/{lesson}/exercises/create', [ExerciseController::class, 'create'])
        ->name('admin.course.lesson.exercises.create');
    Route::post('lessons/{lesson}/exercises', [ExerciseController::class, 'store'])
        ->name('admin.course.lesson.exercises.store');
    Route::post('lessons/{lesson}/exercises/order', [ExerciseController::class, 'updateOrder'])
        ->name('admin.course.lesson.exercises.order');
    Route::get('lessons/{lesson}/exercises/{exercise}/edit', [ExerciseController::class, 'edit'])
        ->name('admin.course.lesson.exercises.edit');
    Route::put('lessons/{lesson}/exercises/{exercise}', [ExerciseController::class, 'update'])
        ->name('admin.course.lesson.exercises.update');
    Route::patch('lessons/{lesson}/exercises/{exercise}/toggle', [ExerciseController::class, 'toggle'])
        ->name('admin.course.lesson.exercises.toggle');
    Route::delete('lessons/{lesson}/exercises/{exercise}', [ExerciseController::class, 'destroy'])
        ->name('admin.course.lesson.exercises.destroy');
});
