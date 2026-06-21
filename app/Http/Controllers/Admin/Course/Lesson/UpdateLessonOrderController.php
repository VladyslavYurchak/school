<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class UpdateLessonOrderController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $data = $request->validate([
            'lessons' => ['required', 'array'],
            'lessons.*.id' => ['required', 'integer'],
            'lessons.*.position' => ['required', 'integer', 'min:1'],
        ]);

        $lessonIds = collect($data['lessons'])->pluck('id');

        abort_unless(
            $lessonIds->unique()->count() === $lessonIds->count()
            && $course->lessons()->whereIn('id', $lessonIds)->count() === $lessonIds->count(),
            422,
            'The lesson order contains invalid records.'
        );

        foreach ($data['lessons'] as $lessonData) {
            $course->lessons()->whereKey($lessonData['id'])
                ->update(['position' => $lessonData['position']]);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }
}
