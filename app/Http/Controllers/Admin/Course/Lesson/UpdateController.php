<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'lesson_type' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $lesson->update([
            'lesson_type' => $validated['lesson_type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'position' => $validated['position'] ?? 0,
            'price' => $validated['price'] ?: null,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('admin.course.show', $lesson->course_id)
            ->with('success', 'Урок оновлено.');
    }
}
