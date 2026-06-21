<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonTestRequest;
use App\Models\LessonTest;
use App\Models\LessonTestOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateController extends Controller
{
    public function __invoke(LessonTestRequest $request, $lessonId, $testId)
    {
        $test = LessonTest::findOrFail($testId);
        abort_unless((int) $test->lesson_id === (int) $lessonId, 404);

        $validated = $request->validated();

        $submittedOptionIds = collect($request->input('options.existing', []))
            ->keys()
            ->map(fn ($id) => (int) $id);

        if (
            $submittedOptionIds->unique()->count() !== $submittedOptionIds->count()
            || $test->options()->whereIn('id', $submittedOptionIds)->count() !== $submittedOptionIds->count()
        ) {
            throw ValidationException::withMessages([
                'options' => 'One or more answer options do not belong to this test.',
            ]);
        }

        DB::transaction(function () use ($request, $test, $validated) {
            $test->update([
                'question' => $validated['question'],
                'is_multiple_choice' => $request->isMultipleChoice(),
            ]);

            foreach ($request->filledOptions() as $data) {
                if ($data['group'] === 'existing') {
                    $option = LessonTestOption::find($data['key']);

                    if ($option && $option->lesson_test_id === $test->id) {
                        $option->update([
                            'option_text' => $data['option_text'],
                            'is_correct' => $data['is_correct'],
                        ]);
                    }

                    continue;
                }

                $test->options()->create([
                    'option_text' => $data['option_text'],
                    'is_correct' => $data['is_correct'],
                ]);
            }
        });

        return redirect()
            ->route('admin.course.lesson.test.create', ['lesson' => $test->lesson_id])
            ->with('success', 'Тест оновлено.');
    }
}
