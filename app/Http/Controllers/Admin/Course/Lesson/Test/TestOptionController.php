<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\LessonTestOption;
use Illuminate\Http\JsonResponse;

class TestOptionController extends Controller
{
    public function __invoke($optionId): JsonResponse
    {
        $option = LessonTestOption::find($optionId);

        if (!$option) {
            return response()->json(['success' => false, 'message' => 'Варіант відповіді не знайдено.'], 404);
        }

        $test = $option->test;

        if (!$test) {
            return response()->json(['success' => false, 'message' => 'Тест не знайдено.'], 404);
        }

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
