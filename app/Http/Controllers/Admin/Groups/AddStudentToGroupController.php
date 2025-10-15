<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\Request;

class AddStudentToGroupController extends Controller
{
    public function __invoke(Request $request, Group $group)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);
        $student->group_id = $group->id;
        $student->save();

        return redirect()->back()->with('success', 'Студента додано до групи.');
    }
}
