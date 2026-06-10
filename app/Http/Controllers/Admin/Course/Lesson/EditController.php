<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Lesson;

class EditController extends Controller
{
    public function __invoke(Lesson $lesson)
    {
        return view('admin.course.lesson.edit', compact('lesson'));
    }
}
