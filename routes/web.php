<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\Course\Lesson\Test\CreateController;
use App\Http\Controllers\Admin\Course\Lesson\Test\DestroyController;
use App\Http\Controllers\Admin\Course\Lesson\Test\EditController;
use App\Http\Controllers\Admin\Course\Lesson\Test\TestOptionController;
use App\Http\Controllers\Admin\Course\Lesson\Test\UpdateController;
use App\Http\Controllers\Admin\Event\CreateController as EventCreateController;
use App\Http\Controllers\Admin\Event\DeleteController as EventDeleteController;
use App\Http\Controllers\Admin\Event\IndexController as EventIndexController;
use App\Http\Controllers\Admin\Event\StoreController as EventStoreController;
use App\Http\Controllers\Admin\Photo\DeleteController;
use App\Http\Controllers\Admin\Photo\IndexController as PhotoIndexController;
use App\Http\Controllers\Admin\Photo\UploadController;
use App\Http\Controllers\Admin\Post\CreateController as PostCreateController;
use App\Http\Controllers\Admin\Post\DeleteController as PostDeleteController;
use App\Http\Controllers\Admin\Post\EditController as PostEditController;
use App\Http\Controllers\Admin\Post\IndexController as PostIndexController;
use App\Http\Controllers\Admin\Post\ShowController as PostShowController;
use App\Http\Controllers\Admin\Post\StoreController as PostStoreController;
use App\Http\Controllers\Admin\Post\UpdateController as PostUpdateController;
use App\Http\Controllers\Admin\StoreController as AdminIndexController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Головна сторінка
Route::get('/', IndexController::class)->name('index');

// Пост
Route::group(['namespace' => 'App\Http\Controllers\Post'], function () {
    Route::get('/posts/{post}', 'ShowController')->name('posts.show');
});

// Авторизація
Auth::routes();

Route::group(['prefix' => 'admin', 'middleware' => 'admin'], function () {
    Route::get('/', AdminIndexController::class)->name('admin.index');

    // Пост
    Route::get('/post', PostIndexController::class)->name('admin.post.index');
    Route::delete('/post/{post}', PostDeleteController::class)->name('admin.post.delete');
    Route::get('/post/create', PostCreateController::class)->name('admin.post.create');
    Route::post('/post', PostStoreController::class)->name('admin.post.store');
    Route::get('/post/edit/{post}', PostEditController::class)->name('admin.post.edit');
    Route::patch('/post/{post}', PostUpdateController::class)->name('admin.post.update');
    Route::get('/post/{post}', PostShowController::class)->name('admin.post.show');

    // Події
    Route::get('/event', EventIndexController::class)->name('admin.event.index');
    Route::get('/event/create', EventCreateController::class)->name('admin.event.create');
    Route::post('/event', EventStoreController::class)->name('admin.event.store');
    Route::delete('/event/{event}', EventDeleteController::class)->name('admin.event.delete');

    // Мови та курси
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

    // створення уроку та редагування
    Route::get('courses/{course}/lessons/create', \App\Http\Controllers\Admin\Course\Lesson\CreateController::class)->name('admin.course.lesson.create');
    Route::post('courses/{course}/lessons', \App\Http\Controllers\Admin\Course\Lesson\StoreController::class)->name('admin.course.lesson.store');
    Route::get('/lesson/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\ShowController::class)->name('admin.course.lesson.show');
    Route::delete('lessons/{lesson}', \App\Http\Controllers\Admin\Course\Lesson\DeleteController::class)->name('admin.course.lesson.delete');
    Route::post('lessons/update-order', \App\Http\Controllers\Admin\Course\Lesson\UpdateLessonOrderController::class)->name('admin.course.lesson.updateOrder');

    // створення тестового блоку та редагування
    Route::get('lessons/{lesson}/test-block/create', CreateController::class)->name('admin.course.lesson.test.create');
    Route::post('lessons/{lesson}/test-block', \App\Http\Controllers\Admin\Course\Lesson\Test\StoreController::class)->name('admin.course.lesson.test.store');
    Route::patch('lessons/{lesson}/test-block/{test}', UpdateController::class)->name('admin.course.lesson.test.update');
    Route::get('lessons/{lesson}/test-block/{test}/edit', EditController::class)->name('admin.course.lesson.test.edit');
    Route::delete('lessons/{lesson}/test-block/{test}', DestroyController::class)->name('admin.course.lesson.test.destroy');
    Route::delete('/course/lesson/test/option/{option}', \App\Http\Controllers\Admin\Course\Lesson\Test\TestOptionController::class)
        ->name('admin.course.lesson.test.option.destroy');
    Route::post('/courses/lesson/test/updateOrder', \App\Http\Controllers\Admin\Course\Lesson\Test\UpdateOrderController::class)
        ->name('admin.course.lesson.test.updateOrder');





    // Фотографії
    Route::group(['prefix' => 'photos'], function () {
        Route::get('/', PhotoIndexController::class)->name('admin.photos.index');
        Route::post('/upload', UploadController::class)->name('admin.photos.upload');
        Route::delete('/delete/{photo}', DeleteController::class)->name('admin.photos.delete');
    });
});

// Інші сторінки
Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::get('/about', [AboutController::class, 'index'])->name('about.index');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::redirect('/home', '/');
