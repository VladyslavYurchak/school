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
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'audio_file' => 'nullable|file|mimes:mp3,wav|max:10240',
            'media_files.*' => 'nullable|file|max:10240',
        ]);

        $media = is_array($lesson->media_files) ? $lesson->media_files : json_decode($lesson->media_files, true) ?? [];

        // Завантаження медіа
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                $media[] = $file->store('lesson_media', 'public');
            }
        }

        // Аудіо (перезапис)
        if ($request->hasFile('audio_file')) {
            if ($lesson->audio_file && Storage::disk('public')->exists($lesson->audio_file)) {
                Storage::disk('public')->delete($lesson->audio_file);
            }
            $lesson->audio_file = $request->file('audio_file')->store('lesson_audio', 'public');
        }

        $lesson->content = $data['content'] ?? null;
        $lesson->video_url = $data['video_url'] ?? null;
        $lesson->media_files = json_encode($media);
        $lesson->save();

        return redirect()->route('admin.course.lesson.main.edit', $lesson->id)
            ->with('success', 'Основна частина уроку оновлена!');
    }
}
