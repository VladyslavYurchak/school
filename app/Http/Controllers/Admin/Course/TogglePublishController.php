<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;
use Illuminate\Http\Request;

class TogglePublishController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $request->validate([
            'is_published' => 'required|boolean',
        ]);

        $course->update([
            'is_published' => $request->is_published,
        ]);
        return response()->json(['message' => 'Статус курсу успішно оновлено']);
    }
}
