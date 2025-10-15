<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;

class DestroyController extends Controller
{
    public function __invoke(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')->with('success', 'Учня видалено');
    }
}
