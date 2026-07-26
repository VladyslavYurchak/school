<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Facades\Storage;

class DeleteController extends Controller
{
    public function __invoke(Course $course)
    {
        if ($course->hasPurchaseHistory()) {
            return redirect()
                ->route('admin.course.index')
                ->with('error', 'Курс має історію оплат і не може бути видалений. Зніміть його з публікації.');
        }

        $lessons = $course->lessons()
            ->with([
                'contentBlocks:id,lesson_id,media_path',
                'exercises.items:id,lesson_exercise_id,audio_path',
            ])
            ->get();

        $lessons
            ->flatMap->contentBlocks
            ->pluck('media_path')
            ->filter()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $lessons
            ->flatMap->exercises
            ->flatMap->items
            ->pluck('audio_path')
            ->filter()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $course->delete();
        return redirect()->route('admin.course.index')->with('success', 'Курс видалено!');
    }
}
