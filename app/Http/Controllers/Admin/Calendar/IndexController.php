<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Group;

class IndexController extends Controller
{
    public function __invoke()
    {
        $teacher = auth()->user()->teacher;
        abort_if(!$teacher, 403, 'У цього користувача немає привʼязаного профілю викладача.');

        $students = Student::query()
            ->where('is_active', true)
            ->where('teacher_id', $teacher->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $groups = Group::query()
            ->where('teacher_id', $teacher->id)
            ->withCount('students')
            ->orderBy('name') // у groups є name — ок
            ->get();



        $groups = Group::query()
            ->where('teacher_id', $teacher->id)
            ->withCount('students')         // зручно показати “N учнів”
            ->orderBy('name')
            ->get();

        return view('admin.calendar.index', compact('students', 'groups'));
    }
}
