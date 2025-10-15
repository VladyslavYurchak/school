<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Homework;

use App\Http\Controllers\Controller;
use App\Models\Lesson;

class EditController extends Controller
{
    // Homework\EditController.php

    public function __invoke(Lesson $lesson)
    {
        $homeworkFiles = is_array($lesson->homework_files)
            ? $lesson->homework_files
            : json_decode($lesson->homework_files, true) ?? [];

        return view('admin.course.lesson.homework.edit', compact('lesson', 'homeworkFiles'));
    }

}
