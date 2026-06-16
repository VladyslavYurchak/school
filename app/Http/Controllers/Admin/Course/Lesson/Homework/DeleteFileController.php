<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Homework;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

class DeleteFileController extends Controller
{
    public function __invoke(Lesson $lesson, string $filename)
    {
        $decodedFilename = urldecode($filename);

        // беремо масив як є
        $files = $lesson->homework_files ?? [];

        if (!in_array($decodedFilename, $files)) {
            return back()->with('error', 'Файл не знайдено серед прикріплених.');
        }

        $disk = Storage::disk('public');

        // видаляємо файл (без exists — не потрібно)
        $disk->delete($decodedFilename);

        // видаляємо з масиву
        $lesson->homework_files = array_values(
            array_filter($files, fn ($file) => $file !== $decodedFilename)
        );

        $lesson->save();

        return back()->with('success', 'Файл успішно видалено.');
    }
}
