<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\UpdateRequest;
use App\Models\Course;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Course $course)
    {
        $course->update($request->validated());

        return redirect()->route('admin.course.index')->with('success', 'Курс оновлено!');
    }
}
