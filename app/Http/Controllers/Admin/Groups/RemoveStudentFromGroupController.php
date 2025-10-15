<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;

class RemoveStudentFromGroupController extends Controller
{
    public function __invoke(Group $group, Student $student)
    {
        if ($student->group_id !== $group->id) {
            abort(403, 'Цей студент не належить до даної групи.');
        }

        $student->group_id = null;
        $student->save();

        return redirect()->back()->with('success', 'Студента видалено з групи.');
    }
}
