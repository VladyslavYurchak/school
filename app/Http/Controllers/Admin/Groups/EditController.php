<?php
namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;

class EditController extends Controller
{
    public function __invoke(Group $group)
    {
        // Студенти, які не належать до жодної групи (тобто вільні)
        $availableStudents = Student::whereNull('group_id')
            ->where('is_active', true)
            ->get();

        // Студенти, які вже є у цій групі
        $students = $group->students; // викликаємо як властивість, а не метод

        // Усі викладачі
        $teachers = Teacher::all();

        return view('admin.groups.edit', compact('group', 'teachers', 'availableStudents', 'students'));
    }
}
