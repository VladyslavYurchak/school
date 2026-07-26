<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class IndexController extends Controller
{
    public function __invoke()
    {
        $teachers = Teacher::with('user')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.teachers.index', compact('teachers'));
    }

}
