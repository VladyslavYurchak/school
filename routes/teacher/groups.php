<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['teacher'])->group(function () {
    Route::get('admin/my-groups', \App\Http\Controllers\Admin\TeacherGroups\MyGroupsController::class)->name('admin.teacher.my_groups');
});
