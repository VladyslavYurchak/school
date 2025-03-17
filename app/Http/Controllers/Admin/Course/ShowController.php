<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;

class ShowController extends Controller
{
    public function __invoke(Course $course)
    {
        $course->load(['lessons' => function ($query) {
            $query->orderBy('position', 'asc');
        }]);

        return view('admin.course.show', compact('course'));    }
}
