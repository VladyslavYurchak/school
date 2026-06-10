<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/calendar-teachers', \App\Http\Controllers\Admin\CalendarTeacher\TeachersIndexController::class)
        ->name('admin.calendar_teachers.teachers.index');

    Route::get('/calendar-teachers/events', \App\Http\Controllers\Admin\CalendarTeacher\TeachersEventsController::class)
        ->name('admin.calendar_teachers.teachers.events');
});
