<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'language_id' => 'required|exists:languages,id',
            'price' => 'nullable|numeric|min:0',
            'is_published' => 'boolean',
        ]);

        $course->update($request->only('title', 'language_id', 'price', 'is_published'));

        return redirect()->route('admin.course.index')->with('success', 'Курс оновлено!');
    }
}
