<?php

use App\Http\Controllers\Admin\Course\Lesson\VocabularyController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('lessons/{lesson}/vocabulary', [VocabularyController::class, 'index'])
        ->name('admin.course.lesson.vocabulary.index');
    Route::get('lessons/{lesson}/vocabulary/create', [VocabularyController::class, 'create'])
        ->name('admin.course.lesson.vocabulary.create');
    Route::post('lessons/{lesson}/vocabulary', [VocabularyController::class, 'store'])
        ->name('admin.course.lesson.vocabulary.store');
    Route::post('lessons/{lesson}/vocabulary/order', [VocabularyController::class, 'updateOrder'])
        ->name('admin.course.lesson.vocabulary.order');
    Route::post('lessons/{lesson}/vocabulary-items/{vocabularyItem}', [VocabularyController::class, 'attach'])
        ->name('admin.course.lesson.vocabulary.attach');
    Route::get('lessons/{lesson}/vocabulary/{link}/edit', [VocabularyController::class, 'edit'])
        ->name('admin.course.lesson.vocabulary.edit');
    Route::put('lessons/{lesson}/vocabulary/{link}', [VocabularyController::class, 'update'])
        ->name('admin.course.lesson.vocabulary.update');
    Route::delete('lessons/{lesson}/vocabulary/{link}', [VocabularyController::class, 'detach'])
        ->name('admin.course.lesson.vocabulary.detach');
});
