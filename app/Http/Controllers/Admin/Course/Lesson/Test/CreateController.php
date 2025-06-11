<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTest;

class CreateController extends Controller
{
    public function __invoke($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $tests = LessonTest::where('lesson_id', $lessonId)->with('options')->orderBy('position')->get();

        return view('admin.course.lesson.test.create', compact('lesson', 'tests'));
    }

}
