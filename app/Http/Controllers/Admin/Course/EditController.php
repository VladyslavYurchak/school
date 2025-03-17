<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;

class EditController extends Controller
{
    public function __invoke(Course $course)
    {
        $languages = Language::all();
        return view('admin.course.edit', compact('course', 'languages'));
    }
}
