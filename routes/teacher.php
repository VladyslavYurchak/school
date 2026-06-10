<?php

use App\Http\Controllers\Admin\StoreController as AdminIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
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
        Route::get('/{group}/members', \App\Http\Controllers\Admin\Calendar\GetGroupMembersController::class)->name('groups.members');
        Route::post('{id}/complete', \App\Http\Controllers\Admin\Calendar\MarkAsCompletedController::class);
        Route::post('{id}/cancel', \App\Http\Controllers\Admin\Calendar\MarkAsCancelledController::class);
        Route::post('{id}/reschedule', \App\Http\Controllers\Admin\Calendar\MarkAsRescheduledController::class);
        Route::put('{id}', \App\Http\Controllers\Admin\Calendar\UpdateEventController::class);
    });

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
