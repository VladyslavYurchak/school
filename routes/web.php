<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\Admin\StoreController as AdminIndexController;
use App\Http\Controllers\Admin\Post\IndexController as PostIndexController;
use App\Http\Controllers\Admin\Post\DeleteController as PostDeleteController;
use App\Http\Controllers\Admin\Post\CreateController as PostCreateController;
use App\Http\Controllers\Admin\Post\StoreController as PostStoreController;
use App\Http\Controllers\Admin\Post\EditController as PostEditController;
use App\Http\Controllers\Admin\Post\UpdateController as PostUpdateController;
use App\Http\Controllers\Admin\Post\ShowController as PostShowController;
use App\Http\Controllers\Admin\Event\IndexController as EventIndexController;
use App\Http\Controllers\Admin\Event\CreateController as EventCreateController;
use App\Http\Controllers\Admin\Event\StoreController as EventStoreController;
use App\Http\Controllers\Admin\Event\DeleteController as EventDeleteController;
use App\Http\Controllers\Admin\Photo\IndexController as PhotoIndexController;
use App\Http\Controllers\Admin\Photo\UploadController;
use App\Http\Controllers\Admin\Photo\DeleteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;

// Головна сторінка
Route::get('/', IndexController::class)->name('index');

// Пост
Route::group(['namespace' => 'App\Http\Controllers\Post'], function () {
    Route::get('/posts/{post}', 'ShowController')->name('posts.show');
});

// Авторизація
Auth::routes();

// Панель адміністратора
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
    Route::get('courses/{course}', \App\Http\Controllers\Admin\Course\ShowController::class)->name('admin.course.show');
    Route::post('/courses/{course}/publish', \App\Http\Controllers\Admin\Course\TogglePublishController::class)->name('admin.courses.publish');

    Route::get('courses/{course}/lessons/create', \App\Http\Controllers\Admin\Lesson\CreateController::class)->name('admin.lesson.create');
    Route::post('courses/{course}/lessons', \App\Http\Controllers\Admin\Lesson\StoreController::class)->name('admin.lesson.store');
    Route::get('/admin/lesson/{lesson}', \App\Http\Controllers\Admin\Lesson\ShowController::class)->name('admin.lesson.show');
    Route::delete('lessons/{lesson}', \App\Http\Controllers\Admin\Lesson\DeleteController::class)->name('admin.lesson.delete');
    Route::post('lessons/update-order', \App\Http\Controllers\Admin\Lesson\UpdateLessonOrderController::class)->name('admin.lesson.updateOrder');


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
