<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class CreateController extends Controller
{
    public function __invoke()
    {
        $teachers = Teacher::all();
        return view('admin.groups.create', compact('teachers'));
    }
}
