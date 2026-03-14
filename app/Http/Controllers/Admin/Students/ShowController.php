<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;

class ShowController extends Controller
{
    public function __invoke(Student $student)
    {
        dd('не треба');
        return view('admin.students.show', compact('student'));
    }
}
