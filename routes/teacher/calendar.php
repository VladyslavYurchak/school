<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
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
        Route::get('/{group}/members', \App\Http\Controllers\Admin\Calendar\GetGroupMembersController::class)
            ->name('groups.members');
        Route::post('{id}/complete', \App\Http\Controllers\Admin\Calendar\MarkAsCompletedController::class)
            ->name('admin.calendar.events.complete');
        Route::post('{id}/cancel', \App\Http\Controllers\Admin\Calendar\MarkAsCancelledController::class)
            ->name('admin.calendar.events.cancel');
        Route::post('{id}/reschedule', \App\Http\Controllers\Admin\Calendar\MarkAsRescheduledController::class)
            ->name('admin.calendar.events.reschedule');
        Route::put('{id}', \App\Http\Controllers\Admin\Calendar\UpdateEventController::class)
            ->name('admin.calendar.events.update');
    });
});
