<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Homework;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

class DestroyController extends Controller
{
    public function __invoke(Lesson $lesson)
    {
        // 1. Видалити файли домашнього завдання з файлової системи
        if ($lesson->homework_files) {
            $files = $lesson->homework_files;

            if (is_array($files)) {
                foreach ($files as $file) {
                    if (Storage::exists($file)) {
                        Storage::delete($file);
                    }
                }
            }
        }

        // 2. Очищуємо поля домашнього завдання в базі
        $lesson->homework_text = null;
        $lesson->homework_files = null;
        $lesson->homework_video_url = null;
        $lesson->save();

        return redirect()->route('admin.course.show', $lesson->course_id)
            ->with('success', 'Домашнє завдання було успішно видалено.');
    }
}
