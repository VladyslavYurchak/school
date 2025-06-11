<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;

class ShowController extends Controller
{
    public function __invoke($lessonId)
    {
        $lesson = Lesson::find($lessonId);
        $courseId = $lesson->course_id;
        $course = Course::find($courseId);
        return view('admin.course.lesson.show', compact('lesson', 'course'));
    }
}
