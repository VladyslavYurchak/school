<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

class DeleteController extends Controller
{
    public function __invoke($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $courseId = $lesson->course_id;

        if ($lesson->hasPurchaseHistory()) {
            return redirect()
                ->route('admin.course.show', $courseId)
                ->with('error', 'Урок має історію оплат і не може бути видалений. Зніміть його з публікації.');
        }

        collect([$lesson->audio_file])
            ->merge($lesson->media_files ?? [])
            ->merge($lesson->homework_files ?? [])
            ->filter()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $lesson->contentBlocks()
            ->whereNotNull('media_path')
            ->pluck('media_path')
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $lesson->exercises()
            ->with('items:id,lesson_exercise_id,audio_path')
            ->get()
            ->flatMap->items
            ->pluck('audio_path')
            ->filter()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $lesson->delete();

        // Перенумерація уроків після видалення
        $lessons = Lesson::where('course_id', $courseId)->orderBy('position')->get();
        foreach ($lessons as $index => $lesson) {
            $lesson->position = $index + 1;
            $lesson->save();
        }

        return redirect()->route('admin.course.show', $courseId);
    }
}
