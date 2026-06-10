<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
    Route::get('admin/my-students', \App\Http\Controllers\Admin\TeacherStudents\MyStudentsController::class)->name('admin.teacher.my_students');
});
