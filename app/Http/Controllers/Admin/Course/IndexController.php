<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;

class IndexController extends Controller
{
    public function __invoke()
    {
        $courses = Course::with('language')->withCount('lessons')->paginate(10);
        $languages = Language::all();
        return view('admin.course.index', compact('courses', 'languages'));    }
}
