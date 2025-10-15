<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Homework;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'homework_text' => 'nullable|string',
            'homework_video_url' => 'nullable|url',
            'homework_files.*' => 'nullable|file|max:10240',
        ]);

        // Отримуємо вже збережені файли
        $existingFiles = $lesson->homework_files ?? [];

        // Додаємо нові файли, якщо є
        if ($request->hasFile('homework_files')) {
            $existingFiles = json_decode($lesson->homework_files, true) ?? [];

            foreach ($request->file('homework_files') as $file) {
                $timestamp = now()->format('Y-m-d_H-i-s');
                $filename = $timestamp . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('homework_files', $filename, 'public');
                $existingFiles[] = $path;
            }

            $lesson->homework_files = json_encode($existingFiles);
        }



        if ($request->filled('homework_text')) {
            $lesson->homework_text = $data['homework_text'];
        }

        $lesson->homework_video_url = $data['homework_video_url'] ?? null;

        $lesson->save();

        return redirect()->route('admin.course.lesson.homework.edit', $lesson->id)
            ->with('success', 'Домашнє завдання оновлено!');
    }

}
