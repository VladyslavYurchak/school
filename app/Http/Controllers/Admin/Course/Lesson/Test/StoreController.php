<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonTestRequest;
use App\Models\Lesson;
use App\Models\LessonTest;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(LessonTestRequest $request, $lessonId)
    {
        $validated = $request->validated();

        $lesson = Lesson::findOrFail($lessonId);
        $maxPosition = $lesson->tests()->max('position') ?? 0;

//        $test = LessonTest::create([
//            'lesson_id' => $lessonId,
//            'question' => $validated['question'],
//        ]);

        DB::transaction(function () use ($lesson, $validated, $maxPosition) {
            $test = $lesson->tests()->create([
                'question' => $validated['question'],
                'position' => $maxPosition + 1,
            ]);

            foreach ($validated['options']['new'] ?? [] as $data) {
                $test->options()->create([
                    'option_text' => $data['option_text'],
                    'is_correct' => !empty($data['is_correct']),
                ]);
            }
        });

        return redirect()
            ->route('admin.course.lesson.test.create', [$lessonId])
            ->with('success', 'Тест створено.');
    }
}
