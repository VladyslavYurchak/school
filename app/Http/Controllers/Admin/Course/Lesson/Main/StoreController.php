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
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:51200',
            'video_url' => 'nullable|url',
            'media_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,mp3,wav,ogg,m4a,doc,docx,ppt,pptx,txt|max:51200',
        ]);

        // Нормалізація YouTube-лінки в embed
        $data['video_url'] = $this->normalizeYoutubeEmbedUrl($data['video_url'] ?? null);

        // Збереження аудіофайлу
        if ($request->hasFile('audio_file')) {
            if ($lesson->audio_file) {
                Storage::disk('public')->delete($lesson->audio_file);
            }

            $data['audio_file'] = $request->file('audio_file')->store('main_audio', 'public');
        } else {
            $data['audio_file'] = $lesson->audio_file;
        }

        $existingFiles = $lesson->media_files ?? [];

        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $timestamp = now()->format('Y-m-d_H-i-s');
                $filename = $timestamp . '_' . $file->getClientOriginalName();

                $path = $file->storeAs('main_media', $filename, 'public');
                $existingFiles[] = $path;
            }
        }

        $lesson->update([
            'content' => $data['content'] ?? null,
            'audio_file' => $data['audio_file'],
            'video_url' => $data['video_url'],
            'media_files' => $existingFiles,
        ]);

        return redirect()->route('admin.course.lesson.main.create', $lesson->id)
            ->with('success', 'Основна частина оновлена!');
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
