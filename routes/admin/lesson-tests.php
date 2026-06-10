<?php

use App\Http\Controllers\Admin\Course\Lesson\Test\CreateController;
use App\Http\Controllers\Admin\Course\Lesson\Test\DestroyController;
use App\Http\Controllers\Admin\Course\Lesson\Test\EditController;
use App\Http\Controllers\Admin\Course\Lesson\Test\UpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('lessons/{lesson}/test-block/create', CreateController::class)->name('admin.course.lesson.test.create');
    Route::post('lessons/{lesson}/test-block', \App\Http\Controllers\Admin\Course\Lesson\Test\StoreController::class)->name('admin.course.lesson.test.store');
    Route::patch('lessons/{lesson}/test-block/{test}', UpdateController::class)->name('admin.course.lesson.test.update');
    Route::get('lessons/{lesson}/test-block/{test}/edit', EditController::class)->name('admin.course.lesson.test.edit');
    Route::delete('lessons/{lesson}/test-block/{test}', DestroyController::class)->name('admin.course.lesson.test.destroy');
    Route::delete('/course/lesson/test/option/{option}', \App\Http\Controllers\Admin\Course\Lesson\Test\TestOptionController::class)
        ->name('admin.course.lesson.test.option.destroy');
    Route::post('/courses/lesson/test/updateOrder', \App\Http\Controllers\Admin\Course\Lesson\Test\UpdateOrderController::class)
        ->name('admin.course.lesson.test.updateOrder');
});
