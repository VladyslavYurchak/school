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
        $fullFilename = 'homework_files/' . $decodedFilename;

        $files = is_array($lesson->homework_files)
            ? $lesson->homework_files
            : json_decode($lesson->homework_files, true) ?? [];

        if (!in_array($fullFilename, $files)) {
            return back()->with('error', 'Файл не знайдено серед прикріплених.');
        }

        // Ось тут треба саме $fullFilename!
        if (Storage::disk('public')->exists($fullFilename)) {
            Storage::disk('public')->delete($fullFilename);
        }

        // Видаляємо зі списку
        $lesson->homework_files = array_values(array_filter($files, fn ($file) => $file !== $fullFilename));
        $lesson->save();

        return back()->with('success', 'Файл успішно видалено.');
    }
}
