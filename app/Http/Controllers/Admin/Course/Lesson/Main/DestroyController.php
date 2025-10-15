<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Main;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;

class DestroyController extends Controller
{
    public function __invoke(Lesson $lesson): RedirectResponse
    {
        // Видаляємо файли media_files (JSON масив)
        if ($lesson->media_files) {
            $files = json_decode($lesson->media_files, true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (Storage::disk('public')->exists($file)) {
                        Storage::disk('public')->delete($file);
                    }
                }
            }
        }

        // Видаляємо аудіофайл
        if ($lesson->audio_file && Storage::disk('public')->exists($lesson->audio_file)) {
            Storage::disk('public')->delete($lesson->audio_file);
        }

        // Тут можна додати видалення інших файлів, якщо треба

        // Очищаємо поля основної частини уроку
        $lesson->content = null;
        $lesson->media_files = null;
        $lesson->audio_file = null;
        $lesson->video_url = null;

        $lesson->save();

        return redirect()->route('admin.course.show', $lesson->course_id)
            ->with('success', 'Основна частина уроку була видалена разом з файлами.');
    }
}
