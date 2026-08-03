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
            'lesson_type' => ['required', 'in:Reading,Listening,Grammar,Speaking,Test'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $lesson->update([
            'lesson_type' => $validated['lesson_type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'position' => $validated['position'] ?? $lesson->position,
            'price' => $request->input('price') !== '' ? ($validated['price'] ?? null) : null,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('admin.course.show', $lesson->course_id)
            ->with('success', 'Урок оновлено.');
    }
}
