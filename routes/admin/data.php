<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['admin'])->group(function () {
    Route::get('admin/data', \App\Http\Controllers\Admin\Data\IndexController::class)
        ->name('admin.data.index');
    Route::get('admin/data/student-attendance/{student}', \App\Http\Controllers\Admin\Data\AttendanceController::class);
});
