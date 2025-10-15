<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Models\User;

class CreateController extends Controller
{
    public function __invoke()
    {
        $users = User::whereNull('role')->orWhere('role', '!=', 'teacher')->get();
        return view('admin.teachers.create', compact('users'));
    }
}
