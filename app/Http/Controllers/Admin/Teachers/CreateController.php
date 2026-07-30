<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Models\User;

class CreateController extends Controller
{
    public function __invoke()
    {
        $users = User::query()
            ->whereDoesntHave('teacher')
            ->whereDoesntHave('student')
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhereIn('role', ['student', 'admin']);
            })
            ->orderBy('name')
            ->get();

        return view('admin.teachers.create', compact('users'));
    }
}
