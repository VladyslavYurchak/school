<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class UpdateLessonOrderController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $lessons = $request->input('lessons');

        foreach ($lessons as $lessonData) {
            Lesson::where('id', $lessonData['id'])
                ->update(['position' => $lessonData['position']]);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }
}
