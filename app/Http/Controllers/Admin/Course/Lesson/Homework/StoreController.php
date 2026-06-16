<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Homework;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;

class StoreController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'homework_text' => 'nullable|string',

            // Не ставимо url, бо можуть вставити youtu.be/... без https://
            'homework_video_url' => 'nullable|string|max:2048',

            'homework_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,mp3,wav,ogg,m4a,doc,docx,ppt,pptx,txt|max:51200',
        ]);

        // Зберігаємо файли домашки і формуємо масив назв
        $files = [];

        if ($request->hasFile('homework_files')) {
            foreach ($request->file('homework_files') as $file) {
                $timestamp = now()->format('Y-m-d_H-i-s');
                $filename = $timestamp . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('homework_files', $filename, 'public');

                $files[] = $path;
            }
        }

        $lesson->homework_text = $data['homework_text'] ?? null;
        $lesson->homework_video_url = $this->normalizeYoutubeEmbedUrl($data['homework_video_url'] ?? null);
        $lesson->homework_files = $files;
        $lesson->save();

        return redirect()
            ->route('admin.course.lesson.homework.edit', $lesson->id)
            ->with('success', 'Домашнє завдання створено!');
    }

    private function normalizeYoutubeEmbedUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        // Якщо вставили без https://
        if (
            str_starts_with($url, 'youtu.be/')
            || str_starts_with($url, 'www.youtube.com/')
            || str_starts_with($url, 'youtube.com/')
        ) {
            $url = 'https://' . $url;
        }

        // Якщо вже embed-лінка
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        // https://youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([^?&]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('/youtube\.com\/watch\?v=([^?&]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // https://www.youtube.com/shorts/VIDEO_ID
        if (preg_match('/youtube\.com\/shorts\/([^?&]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        return $url;
    }
}
