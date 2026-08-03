<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Course;

class CreateController extends Controller
{
    public function __invoke(Course $course)
    {
        $nextPosition = ((int) $course->lessons()->max('position')) + 1;

        return view('admin.course.lesson.create', compact('course', 'nextPosition'));
    }
}
