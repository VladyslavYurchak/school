<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTest;

class EditController extends Controller
{
    public function __invoke($lessonId, $testId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $test = LessonTest::with('options')->findOrFail($testId);

        abort_unless($test->lesson_id === $lesson->id, 404);

        return view('admin.course.lesson.test.edit', compact('lesson', 'test'));
    }
}
