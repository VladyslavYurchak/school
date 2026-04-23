<?php

namespace App\Http\Controllers;

use App\Models\Teacher;

class TeacherPageController extends Controller
{
    public function index()
    {
        $teachers = Teacher::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('public_sort_order')
            ->orderBy('id')
            ->get();

        return view('index.teachers.index', compact('teachers'));
    }
}
