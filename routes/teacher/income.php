<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
    Route::get('admin/teacher_income', App\Http\Controllers\Admin\Teacher_income\IndexController::class)->name('admin.teacher_income.index');
});
