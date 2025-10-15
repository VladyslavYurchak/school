<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Homework;

use App\Http\Controllers\Controller;
use App\Models\Lesson;

class CreateController extends Controller
{
    public function __invoke(Lesson $lesson)
    {
        return view('admin.course.lesson.homework.create', compact('lesson'));
    }
}


