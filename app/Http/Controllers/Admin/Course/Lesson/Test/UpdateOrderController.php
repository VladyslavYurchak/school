<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\LessonTest;
use Illuminate\Http\Request;

class UpdateOrderController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:lesson_tests,id',
            'order.*.position' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            $test = LessonTest::find($item['id']);
            if ($test) {
                $test->position = $item['position'];
                $test->save();
            }
        }

        return response()->json(['success' => true]);
    }
}
