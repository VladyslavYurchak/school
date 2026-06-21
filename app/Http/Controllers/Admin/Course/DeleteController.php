<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Facades\Storage;

class DeleteController extends Controller
{
    public function __invoke(Course $course)
    {
        $course->lessons()
            ->with('contentBlocks:id,lesson_id,media_path')
            ->get()
            ->flatMap->contentBlocks
            ->pluck('media_path')
            ->filter()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $course->delete();
        return redirect()->route('admin.course.index')->with('success', 'Курс видалено!');
    }
}
