<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;

class EditController extends Controller
{
    public function __invoke(Teacher $teacher)
    {
        $teacher->load('user');

        $users = User::query()
            ->whereKey($teacher->user_id)
            ->get();

        return view('admin.teachers.edit', compact('teacher', 'users'));
    }
}
