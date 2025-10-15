<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Main;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

class DeleteFileController extends Controller
{
    public function __invoke(Lesson $lesson, string $filename)
    {
        $decodedFilename = urldecode($filename);
        $fullPath = 'main_media/' . $decodedFilename;

        $files = is_array($lesson->media_files) ? $lesson->media_files : json_decode($lesson->media_files, true) ?? [];

        if (!in_array($fullPath, $files)) {
            return back()->with('error', 'Файл не знайдено.');
        }

        if (Storage::disk('public')->exists($fullPath)) {
            Storage::disk('public')->delete($fullPath);
        }

        $lesson->media_files = array_values(array_filter($files, fn($f) => $f !== $fullPath));
        $lesson->save();

        return back()->with('success', 'Файл успішно видалено.');
    }

}
