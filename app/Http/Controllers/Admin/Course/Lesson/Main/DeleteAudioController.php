<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Main;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

class DeleteAudioController extends Controller
{
    public function __invoke(Lesson $lesson)
    {
        if ($lesson->audio_file && Storage::disk('public')->exists($lesson->audio_file)) {
            Storage::disk('public')->delete($lesson->audio_file);
        }

        $lesson->audio_file = null;
        $lesson->save();

        return back()->with('success', 'Аудіофайл успішно видалено.');
    }
}
