<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Main;

use App\Http\Controllers\Controller;
use App\Models\Lesson;

class CreateController extends Controller
{
    public function __invoke(Lesson $lesson)
    {
        $mediaFiles = is_array($lesson->media_files)
            ? $lesson->media_files
            : json_decode($lesson->media_files, true) ?? [];

        return view('admin.course.lesson.main.create', compact('lesson', 'mediaFiles'));
    }
}
