<?php

namespace App\Http\Controllers\Admin\Teacher_students;

use App\Http\Controllers\Controller;
use App\Models\Student;

class MyStudentsController extends Controller
{
    public function __invoke()
    {
        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            abort(403, 'Доступ заборонено');
        }

        // Вибираємо активних студентів, які прив'язані до викладача
        $students = Student::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->get();

        return view('admin.teacher_students.my_students', compact('students'));
    }
}
