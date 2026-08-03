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

        $lessonIds = collect($data['lessons'])->pluck('id')->map(fn ($id) => (int) $id);
        $currentLessonIds = $course->lessons()->pluck('id')->map(fn ($id) => (int) $id);

        abort_unless(
            $lessonIds->unique()->count() === $lessonIds->count()
            && $lessonIds->sort()->values()->all() === $currentLessonIds->sort()->values()->all(),
            422,
            'The lesson order must contain every lesson from this course.'
        );

        foreach ($data['lessons'] as $index => $lessonData) {
            $course->lessons()->whereKey($lessonData['id'])
                ->update(['position' => $index + 1]);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }
}
