<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\LessonTestOption;
use App\Models\TestOption;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestOptionController extends Controller
{
    /**
     * Видалення опції тесту
     */
    public function __invoke($optionId): JsonResponse
    {
        $option = LessonTestOption::find($optionId);

        if (!$option) {
            return response()->json(['success' => false, 'message' => 'Опція не знайдена'], 404);
        }

        $test = $option->test;

        if (!$test) {
            return response()->json(['success' => false, 'message' => 'Тест не знайдено'], 404);
        }

        if ($test->options()->count() <= 3) {
            return response()->json(['success' => false, 'message' => 'Має бути мінімум 3 варіанти'], 400);
        }

        $option->delete();

        return response()->json(['success' => true, 'message' => 'Видалено']);
    }
}
