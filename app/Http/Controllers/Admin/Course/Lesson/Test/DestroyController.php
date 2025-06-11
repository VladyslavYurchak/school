<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTest;

class DestroyController extends Controller
{
    public function __invoke($lessonId, $testId)
    {
        $test = LessonTest::findOrFail($testId);
        $test->delete();

        // Оновлюємо позиції тестів після видалення
        $tests = LessonTest::where('lesson_id', $lessonId)->orderBy('position')->get();
        $tests->each(function ($test, $index) {
            $test->update(['position' => $index + 1]); // Починаємо нумерацію з 1
        });

        return redirect()->route('admin.course.lesson.test.create', $lessonId)->with('success', 'Тест успішно видалено');
    }

}
