<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class EditController extends Controller
{
    public function __invoke(Teacher $teacher)
    {
        $user = $teacher->user; // зв’язаний користувач
        return view('admin.teachers.edit', compact('teacher', 'user'));
    }
}
