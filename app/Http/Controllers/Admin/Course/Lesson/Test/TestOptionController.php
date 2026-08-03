<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTest;
use App\Models\LessonTestOption;
use Illuminate\Http\JsonResponse;

class TestOptionController extends Controller
{
    public function __invoke(
        Lesson $lesson,
        LessonTest $test,
        LessonTestOption $option
    ): JsonResponse {
        abort_unless($test->lesson_id === $lesson->id, 404);
        abort_unless($option->lesson_test_id === $test->id, 404);

        if ($test->options()->count() <= 3) {
            return response()->json(['success' => false, 'message' => 'У тесті має бути щонайменше 3 відповіді.'], 400);
        }

        if ($option->is_correct && $test->options()->where('is_correct', true)->count() <= 1) {
            return response()->json(['success' => false, 'message' => 'Має залишитися хоча б одна правильна відповідь.'], 400);
        }

        $option->delete();

        $test->update([
            'is_multiple_choice' => $test->options()->where('is_correct', true)->count() > 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Видалено.']);
    }
}
