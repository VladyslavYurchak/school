<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Main;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'content' => ['nullable', 'string'],

            // Не ставимо url, бо YouTube часто вставляють без https://
            'video_url' => ['nullable', 'string', 'max:2048'],

            // 50 MB для аудіо
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],

            // 50 MB на кожен файл
            'media_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,mp3,wav,ogg,m4a,doc,docx,ppt,pptx,txt', 'max:51200'],
        ]);

        $media = $lesson->media_files;

        if (is_string($media)) {
            $media = json_decode($media, true) ?: [];
        }

        if (!is_array($media)) {
            $media = [];
        }

        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $media[] = $file->store('lesson_media', 'public');
            }
        }

        if ($request->hasFile('audio_file')) {
            if ($lesson->audio_file && Storage::disk('public')->exists($lesson->audio_file)) {
                Storage::disk('public')->delete($lesson->audio_file);
            }

            $lesson->audio_file = $request->file('audio_file')->store('lesson_audio', 'public');
        }

        $lesson->content = $data['content'] ?? null;

        // Тут автоматично перетворюємо YouTube-лінку в embed
        $lesson->video_url = $this->normalizeYoutubeEmbedUrl($data['video_url'] ?? null);

        $lesson->media_files = $media;

        $lesson->save();

        return redirect()
            ->route('admin.course.lesson.main.edit', $lesson->id)
            ->with('success', 'Основна частина уроку оновлена!');
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
