<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\UpdateRequest;
use App\Models\Course;
use App\Models\Language;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Course $course)
    {
        $request->validated();

        $course->update($request->only('title', 'language_id', 'price', 'is_published'));

        return redirect()->route('admin.course.index')->with('success', 'Курс оновлено!');
    }
}
