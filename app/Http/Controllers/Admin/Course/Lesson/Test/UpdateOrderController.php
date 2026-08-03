<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class UpdateOrderController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:lesson_tests,id',
            'order.*.position' => 'required|integer|min:1',
        ]);

        $testIds = collect($data['order'])->pluck('id')->map(fn ($id) => (int) $id);
        $currentTestIds = $lesson->tests()->pluck('id')->map(fn ($id) => (int) $id);

        abort_unless(
            $testIds->unique()->count() === $testIds->count()
            && $testIds->sort()->values()->all() === $currentTestIds->sort()->values()->all(),
            422,
            'The test order must contain every test from this lesson.'
        );

        foreach ($data['order'] as $index => $item) {
            $lesson->tests()->whereKey($item['id'])->update([
                'position' => $index + 1,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
