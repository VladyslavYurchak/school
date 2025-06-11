<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Lesson;

class DeleteController extends Controller
{
    public function __invoke($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $courseId = $lesson->course_id;
        $lesson->delete();

        // Перенумерація уроків після видалення
        $lessons = Lesson::where('course_id', $courseId)->orderBy('position')->get();
        foreach ($lessons as $index => $lesson) {
            $lesson->position = $index + 1;
            $lesson->save();
        }

        return redirect()->route('admin.course.show', $courseId);
    }
}
