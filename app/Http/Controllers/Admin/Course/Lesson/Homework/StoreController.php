<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Homework;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'homework_text' => 'nullable|string',
            'homework_video_url' => 'nullable|url',
            'homework_files.*' => 'nullable|file|max:10240', // max 10 MB
        ]);

        // Зберігаємо файли домашки і формуємо масив назв
        $files = [];
        if ($request->hasFile('homework_files')) {
            foreach ($request->file('homework_files') as $file) {
                $timestamp = now()->format('Y-m-d_H-i-s'); // напр. 2025-06-25_17-20-30
                $filename = $timestamp . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('homework_files', $filename, 'public');

                $files[] = $path;
            }
        }

        $lesson->homework_text = $data['homework_text'] ?? null;
        $lesson->homework_video_url = $data['homework_video_url'] ?? null;
        $lesson->homework_files = json_encode($files);
        $lesson->save();

        return redirect()->route('admin.course.lesson.homework.edit', $lesson->id)
            ->with('success', 'Домашнє завдання створено!');
    }
}



