<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
    Route::get('data', \App\Http\Controllers\Admin\Data\IndexController::class)
        ->name('admin.data.index');
    Route::get('admin/data/student-attendance/{student}', \App\Http\Controllers\Admin\Data\AttendanceController::class);
});
