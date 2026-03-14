<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\History\HistoryActionsController;
use Illuminate\Support\Facades\Auth;

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
Auth::routes(['verify' => true]);
// routes/web.php

Route::group(['prefix' => 'admin', 'middleware' => 'admin'], function () {

    Route::get('/history-actions', HistoryActionsController::class)
        ->name('admin.history_actions.index');

    Route::get('/calendar-teachers', \App\Http\Controllers\Admin\Calendar_teacher\TeachersIndexController::class)
        ->name('admin.calendar_teachers.teachers.index');

    Route::get('/calendar-teachers/events', \App\Http\Controllers\Admin\Calendar_teacher\TeachersEventsController::class)
        ->name('admin.calendar_teachers.teachers.events');
});


Route::group(['prefix' => 'admin', 'middleware' => 'admin'], function () {
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


    // Main block
    Route::get('lessons/{lesson}/main-block/create', \App\Http\Controllers\Admin\Course\Lesson\Main\CreateController::class)->name('admin.course.lesson.main.create');
    Route::post('lessons/{lesson}/main-block', \App\Http\Controllers\Admin\Course\Lesson\Main\StoreController::class)->name('admin.course.lesson.main.store');
    Route::get('lessons/{lesson}/main-block/edit', \App\Http\Controllers\Admin\Course\Lesson\Main\EditController::class)->name('admin.course.lesson.main.edit');
    Route::put('lessons/{lesson}/main-block/', \App\Http\Controllers\Admin\Course\Lesson\Main\UpdateController::class)->name('admin.course.lesson.main.update');
    Route::delete('lessons/{lesson}/main-block/audio', \App\Http\Controllers\Admin\Course\Lesson\Main\DeleteAudioController::class)
        ->name('admin.course.lesson.main.audio.delete');
    Route::delete('lessons/{lesson}/main-block/{filename}', \App\Http\Controllers\Admin\Course\Lesson\Main\DeleteFileController::class)
        ->name('admin.course.lesson.main.file.delete')
        ->where('filename', '.*');
    Route::delete('lesson/{lesson}/main', \App\Http\Controllers\Admin\Course\Lesson\Main\DestroyController::class)
        ->name('admin.course.lesson.main.destroy');


// Homework block
    Route::get('lessons/{lesson}/homework-block/create', \App\Http\Controllers\Admin\Course\Lesson\Homework\CreateController::class)->name('admin.course.lesson.homework.create');
    Route::post('lessons/{lesson}/homework-block', \App\Http\Controllers\Admin\Course\Lesson\Homework\StoreController::class)->name('admin.course.lesson.homework.store');
    Route::post('admin/lessons/{lesson}/homework-block', \App\Http\Controllers\Admin\Course\Lesson\Homework\StoreController::class)
        ->name('admin.course.lesson.homework.store');
    Route::put('lessons/{lesson}/homework-block', \App\Http\Controllers\Admin\Course\Lesson\Homework\UpdateController::class)
        ->name('admin.course.lesson.homework.update');
    Route::get('lessons/{lesson}/homework-block/edit', \App\Http\Controllers\Admin\Course\Lesson\Homework\EditController::class)
        ->name('admin.course.lesson.homework.edit');
    Route::delete('lessons/{lesson}/homework-block', \App\Http\Controllers\Admin\Course\Lesson\Homework\DestroyController::class)
        ->name('admin.course.lesson.homework.destroy');
    Route::delete('lessons/{lesson}/homework-file/{filename}', \App\Http\Controllers\Admin\Course\Lesson\Homework\DeleteFileController::class)
        ->name('admin.course.lesson.homework.file.delete');


    //адміністрація школою
    Route::prefix('students')->name('admin.students.')->group(function () {
        Route::get('/main', \App\Http\Controllers\Admin\Students\IndexController::class)->name('index');
        Route::get('/create', \App\Http\Controllers\Admin\Students\CreateController::class)->name('create');
        Route::post('/store', \App\Http\Controllers\Admin\Students\StoreController::class)->name('store');

        // Потрібно, щоб маршрути з параметрами були після конкретних
        Route::get('/{student}/edit', \App\Http\Controllers\Admin\Students\EditController::class)->name('edit');
        Route::put('/{student}', \App\Http\Controllers\Admin\Students\UpdateController::class)->name('update');
        Route::delete('/{student}', \App\Http\Controllers\Admin\Students\DestroyController::class)->name('destroy');

        Route::post('/{student}/subscription', \App\Http\Controllers\Admin\Students\Subscription\StoreController::class)
            ->name('subscriptions.store');
        Route::delete('/{student}/subscriptions/{month}', \App\Http\Controllers\Admin\Students\Subscription\DestroyController::class)
            ->name('subscriptions.destroyMonth');

        Route::get('/{student}/single-payments', \App\Http\Controllers\Admin\Students\Subscription\Single\IndexController::class)
            ->name('subscriptions.single.index'); // список поразових оплат з фільтром місяця (AJAX або звичайний)

        Route::delete('/{student}/single-payments/{payment}', \App\Http\Controllers\Admin\Students\Subscription\Single\DestroyController::class)
            ->name('subscriptions.single.destroy'); // скасування ко

    });


    Route::prefix('teachers')->name('admin.teachers.')->group(function () {
        Route::get('/', \App\Http\Controllers\Admin\Teachers\IndexController::class)->name('index');
        Route::get('/create', \App\Http\Controllers\Admin\Teachers\CreateController::class)->name('create');
        Route::post('/', \App\Http\Controllers\Admin\Teachers\StoreController::class)->name('store');
        Route::get('/{teacher}/edit', \App\Http\Controllers\Admin\Teachers\EditController::class)->name('edit');
        Route::put('/{teacher}', \App\Http\Controllers\Admin\Teachers\UpdateController::class)->name('update');
        Route::delete('/{teacher}', \App\Http\Controllers\Admin\Teachers\DestroyController::class)->name('destroy');
    });

    // Фотографії
    Route::group(['prefix' => 'photos'], function () {
        Route::get('/', PhotoIndexController::class)->name('admin.photos.index');
        Route::post('/upload', UploadController::class)->name('admin.photos.upload');
        Route::delete('/delete/{photo}', DeleteController::class)->name('admin.photos.delete');
    });

    Route::prefix('groups')->name('admin.groups.')->group(function () {
        Route::get('/', \App\Http\Controllers\Admin\Groups\IndexController::class)->name('index');
        Route::get('/create', \App\Http\Controllers\Admin\Groups\CreateController::class)->name('create');
        Route::post('/', \App\Http\Controllers\Admin\Groups\StoreController::class)->name('store');
        Route::get('/{group}/edit', \App\Http\Controllers\Admin\Groups\EditController::class)->name('edit');
        Route::put('/{group}', \App\Http\Controllers\Admin\Groups\UpdateController::class)->name('update');
        Route::delete('/{group}', \App\Http\Controllers\Admin\Groups\DestroyController::class)->name('destroy');
        Route::post('/{group}/add-student', \App\Http\Controllers\Admin\Groups\AddStudentToGroupController::class)
            ->name('add-student');
        Route::delete('/{group}/remove-student/{student}', \App\Http\Controllers\Admin\Groups\RemoveStudentFromGroupController::class)
            ->name('remove-student');
    });

    Route::get('/information', \App\Http\Controllers\Admin\Information\IndexController::class)
        ->name('admin.information.index');
});

Route::group(['middleware' => ['teacher']], function () {
    Route::get('/main', AdminIndexController::class)->name('admin.index');
    Route::get('admin/teacher_income', App\Http\Controllers\Admin\Teacher_income\IndexController::class)->name('admin.teacher_income.index');
    Route::get('admin/my-groups', \App\Http\Controllers\Admin\Teacher_groups\MyGroupsController::class)->name('admin.teacher.my_groups');
    Route::get('admin/my-students', \App\Http\Controllers\Admin\Teacher_students\MyStudentsController::class)->name('admin.teacher.my_students');


    Route::get('admin/calendar', \App\Http\Controllers\Admin\Calendar\IndexController::class)->name('admin.calendar.index');
    Route::get('admin/calendar-events', \App\Http\Controllers\Admin\Calendar\EventController::class)->name('admin.calendar.events');
    Route::post('admin/calendar-events', \App\Http\Controllers\Admin\Calendar\StoreEventController::class)->name('admin.calendar.store');
    Route::post('admin/calendar/group-attendance', \App\Http\Controllers\Admin\Calendar\MarkGroupAttendanceController::class)
        ->name('admin.calendar.group-attendance');
    Route::post('admin/calendar/group-lessons/{id}/reschedule', \App\Http\Controllers\Admin\Calendar\MarkGroupRescheduledController::class)
        ->name('admin.calendar.group-lessons.reschedule');
    Route::post('admin/calendar/group-lessons/{id}/cancel', \App\Http\Controllers\Admin\Calendar\MarkGroupCancelledController::class)
        ->name('admin.calendar.group-lessons.cancel');


    Route::prefix('admin/calendar-events')->group(function () {
        Route::get('/{group}/members', \App\Http\Controllers\Admin\Calendar\GetGroupMembersController::class) ->name('groups.members');
        Route::post('{id}/complete', \App\Http\Controllers\Admin\Calendar\MarkAsCompletedController::class);
        Route::post('{id}/cancel', \App\Http\Controllers\Admin\Calendar\MarkAsCancelledController::class);
        Route::post('{id}/reschedule', \App\Http\Controllers\Admin\Calendar\MarkAsRescheduledController::class);
        Route::put('{id}', \App\Http\Controllers\Admin\Calendar\UpdateEventController::class);
    });

    // додавання студентам абонементів

    // додавання абонементів
    Route::prefix('/subscription-templates')->name('admin.subscription-templates.')->group(function () {
        Route::get('/create', \App\Http\Controllers\Admin\SubscriptionTemplate\CreateController::class)->name('create');
        Route::post('/', \App\Http\Controllers\Admin\SubscriptionTemplate\StoreController::class)->name('store');
        Route::get('/', \App\Http\Controllers\Admin\SubscriptionTemplate\IndexController::class)->name('index');

        Route::get('/{subscriptionTemplate}/edit', \App\Http\Controllers\Admin\SubscriptionTemplate\EditController::class)->name('edit');
        Route::put('/{subscriptionTemplate}', \App\Http\Controllers\Admin\SubscriptionTemplate\UpdateController::class)->name('update');
        Route::delete('/{subscriptionTemplate}', \App\Http\Controllers\Admin\SubscriptionTemplate\DestroyController::class)->name('destroy');
    });

    Route::get('data', \App\Http\Controllers\Admin\Data\IndexController::class)
        ->name('admin.data.index');
    Route::get('admin/data/student-attendance/{student}', \App\Http\Controllers\Admin\Data\AttendanceController::class);
});




// Інші сторінки
Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::get('/about', [AboutController::class, 'index'])->name('about.index');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::redirect('/home', '/');

// routes/web.php
Route::get('/debug/time', function () {
    return response()->json([
        'laravel_now'   => now()->toDateTimeString(),
        'laravel_tz'    => now()->getTimezone()->getName(),
        'php_date'      => date('c'),
        'ini_timezone'  => ini_get('date.timezone'),
        'app_timezone'  => config('app.timezone'),
        'system_date'   => trim(@shell_exec('date +"%F %T %Z"') ?: 'N/A'),
    ]);
});
