<?php

use App\Http\Controllers\Admin\Course\Lesson\Test\CreateController;
use App\Http\Controllers\Admin\Course\Lesson\Test\DestroyController;
use App\Http\Controllers\Admin\Course\Lesson\Test\EditController;
use App\Http\Controllers\Admin\Course\Lesson\Test\UpdateController;
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

    Route::get('courses/{course}/lessons/create', \App\Http\Controllers\Admin\Course\Lesson\CreateController::class)->name('admin.course.lesson.create');
    Route::post('courses/{course}/lessons', \App\Http\Controllers\Admin\Course\Lesson\StoreController::class)->name('admin.course.lesson.store');
    Route::get('/lesson/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\ShowController::class)->name('admin.course.lesson.show');
    Route::delete('lessons/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\DeleteController::class)->name('admin.course.lesson.delete');
    Route::post('lessons/update-order', \App\Http\Controllers\Admin\Course\Lesson\UpdateLessonOrderController::class)->name('admin.course.lesson.updateOrder');
    Route::get('lessons/{lesson}/edit', \App\Http\Controllers\Admin\Course\Lesson\EditController::class)
        ->name('admin.course.lesson.edit');
    Route::put('lessons/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\UpdateController::class)
        ->name('admin.course.lesson.update');

    Route::get('lessons/{lesson}/test-block/create', CreateController::class)->name('admin.course.lesson.test.create');
    Route::post('lessons/{lesson}/test-block', \App\Http\Controllers\Admin\Course\Lesson\Test\StoreController::class)->name('admin.course.lesson.test.store');
    Route::patch('lessons/{lesson}/test-block/{test}', UpdateController::class)->name('admin.course.lesson.test.update');
    Route::get('lessons/{lesson}/test-block/{test}/edit', EditController::class)->name('admin.course.lesson.test.edit');
    Route::delete('lessons/{lesson}/test-block/{test}', DestroyController::class)->name('admin.course.lesson.test.destroy');
    Route::delete('/course/lesson/test/option/{option}', \App\Http\Controllers\Admin\Course\Lesson\Test\TestOptionController::class)
        ->name('admin.course.lesson.test.option.destroy');
    Route::post('/courses/lesson/test/updateOrder', \App\Http\Controllers\Admin\Course\Lesson\Test\UpdateOrderController::class)
        ->name('admin.course.lesson.test.updateOrder');

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
