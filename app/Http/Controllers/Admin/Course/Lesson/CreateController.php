<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Course;

class CreateController extends Controller
{
    public function __invoke($courseId)
    {
        $course = Course::findOrFail($courseId);
        return view('admin.course.lesson.create', compact('course'));    }
}
