<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Lesson;

class ShowController extends Controller
{
    public function __invoke(Lesson $lesson)
    {
        $mediaFiles = is_string($lesson->media_files)
            ? json_decode($lesson->media_files, true)
            : ($lesson->media_files ?? []);

        $homeworkFiles = is_string($lesson->homework_files)
            ? json_decode($lesson->homework_files, true)
            : ($lesson->homework_files ?? []);

        $tests = $lesson->tests()->with('options')->orderBy('position')->get();

        return view('admin.course.lesson.show', compact('lesson', 'mediaFiles', 'homeworkFiles', 'tests'));
    }
}
