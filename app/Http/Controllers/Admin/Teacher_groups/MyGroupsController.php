<?php

namespace App\Http\Controllers\Admin\Teacher_groups;

use App\Http\Controllers\Controller;
use App\Models\Group;

class MyGroupsController extends Controller
{
    public function __invoke()
    {
        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            abort(403, 'Доступ заборонено');
        }

        // Витягуємо всі групи, прив’язані до цього викладача
        $groups = Group::withCount('students') // опціонально: рахуємо кількість студентів
        ->where('teacher_id', $teacher->id)
            ->orderBy('name')
            ->get();

        return view('admin.teacher_groups.my_groups', compact('groups'));
    }
}
