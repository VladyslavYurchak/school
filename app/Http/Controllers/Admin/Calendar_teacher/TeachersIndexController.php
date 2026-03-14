<?php

namespace App\Http\Controllers\Admin\Calendar_teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class TeachersIndexController extends Controller
{
    public function __invoke()
    {
        $teachers = Teacher::orderBy('last_name')->get();

        return view('admin.calendar_teachers.teachers', compact('teachers'));
    }
}
