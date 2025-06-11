<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTest;
use App\Models\LessonTestOption;
use Illuminate\Http\Request;

class EditController extends Controller
{
    public function __invoke($lessonId, $testId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $test = LessonTest::with('options')->findOrFail($testId);

        return view('admin.course.lesson.test.edit', compact('lesson', 'test'));
    }

}
