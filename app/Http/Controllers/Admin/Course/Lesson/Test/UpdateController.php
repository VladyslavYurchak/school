<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonTestRequest;
use App\Models\LessonTest;
use App\Models\LessonTestOption;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    public function __invoke(LessonTestRequest $request, $lessonId, $testId)
    {
        $test = LessonTest::findOrFail($testId);

        // Оновлюємо запитання
        $test->update(['question' => $request->validated()['question']]);

        DB::transaction(function () use ($request, $test) {
            // Оновлення існуючих варіантів
            foreach ($request->input('options.existing', []) as $optionId => $data) {
                $option = LessonTestOption::find($optionId);
                if ($option && $option->lesson_test_id === $test->id) {
                    $option->update([
                        'option_text' => $data['option_text'] ?? '',
                        'is_correct' => isset($data['is_correct']) && $data['is_correct'],
                    ]);
                }
            }

            // Додавання нових варіантів
            foreach ($request->input('options.new', []) as $data) {
                if (!empty($data['option_text'])) {
                    $test->options()->create([
                        'option_text' => $data['option_text'],
                        'is_correct' => isset($data['is_correct']) && $data['is_correct'],
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.course.lesson.test.create', [$test->lesson_id, $test->id])
            ->with('success', 'Тест оновлено.');
    }
}
