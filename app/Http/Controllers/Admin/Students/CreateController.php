<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class CreateController extends Controller
{
    public function __invoke()
    {
        $teachers = Teacher::all();
        return view('admin.students.create');
    }
}
