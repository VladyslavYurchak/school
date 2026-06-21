<?php

use App\Http\Controllers\Admin\Course\Lesson\ContentBlockController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('lessons/{lesson}/content-blocks', [ContentBlockController::class, 'index'])
        ->name('admin.course.lesson.blocks.index');
    Route::get('lessons/{lesson}/content-blocks/create', [ContentBlockController::class, 'create'])
        ->name('admin.course.lesson.blocks.create');
    Route::post('lessons/{lesson}/content-blocks', [ContentBlockController::class, 'store'])
        ->name('admin.course.lesson.blocks.store');
    Route::post('lessons/{lesson}/content-blocks/order', [ContentBlockController::class, 'updateOrder'])
        ->name('admin.course.lesson.blocks.order');
    Route::get('lessons/{lesson}/content-blocks/{block}/edit', [ContentBlockController::class, 'edit'])
        ->name('admin.course.lesson.blocks.edit');
    Route::put('lessons/{lesson}/content-blocks/{block}', [ContentBlockController::class, 'update'])
        ->name('admin.course.lesson.blocks.update');
    Route::patch('lessons/{lesson}/content-blocks/{block}/toggle', [ContentBlockController::class, 'toggle'])
        ->name('admin.course.lesson.blocks.toggle');
    Route::delete('lessons/{lesson}/content-blocks/{block}', [ContentBlockController::class, 'destroy'])
        ->name('admin.course.lesson.blocks.destroy');
});
