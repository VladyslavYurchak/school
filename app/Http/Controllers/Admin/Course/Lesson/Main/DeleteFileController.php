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
        $files = is_array($lesson->media_files)
            ? $lesson->media_files
            : json_decode($lesson->media_files, true) ?? [];

        $storedPath = in_array($decodedFilename, $files, true)
            ? $decodedFilename
            : 'main_media/' . $decodedFilename;

        if (!in_array($storedPath, $files, true)) {
            return back()->with('error', 'File was not found.');
        }

        Storage::disk('public')->delete($storedPath);

        $lesson->media_files = array_values(
            array_filter($files, fn ($file) => $file !== $storedPath)
        );
        $lesson->save();

        return back()->with('success', 'File deleted.');
    }
}
