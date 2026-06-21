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

        $testIds = collect($data['order'])->pluck('id');

        abort_unless(
            $testIds->unique()->count() === $testIds->count()
            && $lesson->tests()->whereIn('id', $testIds)->count() === $testIds->count(),
            422,
            'The test order contains invalid records.'
        );

        foreach ($data['order'] as $item) {
            $lesson->tests()->whereKey($item['id'])->update([
                'position' => $item['position'],
            ]);
        }

        return response()->json(['success' => true]);
    }
}
