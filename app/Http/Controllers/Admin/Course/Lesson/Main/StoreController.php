<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Main;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'content' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'video_url' => 'nullable|url',
            'media_files.*' => 'nullable|file|max:10240',
        ]);

        // Збереження аудіофайлу
        if ($request->hasFile('audio_file')) {
            $data['audio_file'] = $request->file('audio_file')->store('main_audio', 'public');
        } else {
            // Якщо не оновлювали — залишаємо старий
            $data['audio_file'] = $lesson->audio_file;
        }

        // Обробка media_files (масив)
        $existingFiles = is_array($lesson->media_files)
            ? $lesson->media_files
            : json_decode($lesson->media_files, true) ?? [];

        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $timestamp = now()->format('Y-m-d_H-i-s'); // напр. 2025-06-25_17-20-30
                $filename = $timestamp . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('main_media', $filename, 'public');
                $existingFiles[] = $path;
            }
        }

        $data['media_files'] = json_encode($existingFiles);

        $lesson->update([
            'content' => $data['content'] ?? null,
            'audio_file' => $data['audio_file'],
            'video_url' => $data['video_url'] ?? null,
            'media_files' => $data['media_files'],
        ]);

        return redirect()->route('admin.course.lesson.main.create', $lesson->id)
            ->with('success', 'Основна частина оновлена!');
    }
}
