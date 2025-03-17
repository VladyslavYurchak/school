<?php

namespace App\Http\Controllers\Admin\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request, $courseId)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'lesson_type' => 'required|string',
            'video_url' => 'nullable|url',
            'homework_text' => 'nullable|string',
            'homework_video_url' => 'nullable|url',
            'media_files.*' => 'file|mimes:jpg,jpeg,png,mp3, mp4,mov,avi,wmv,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:20480',
            'homework_file.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,ppt,pptx|max:20480',
        ], [
            'media_files.*.mimes' => 'Завантажте файл у форматах: jpg, jpeg, png, mp4, mov, avi, wmv, pdf, doc, docx, xls, xlsx, ppt, pptx, txt, zip, rar.',
            'homework_file.*.mimes' => 'Завантажте файл у форматах: jpg, jpeg, png, pdf, doc, docx, ppt, pptx.',
            'media_files.*.max' => 'Розмір файлу не повинен перевищувати 20MB.',
            'homework_file.*.max' => 'Розмір файлу не повинен перевищувати 20MB.',
        ]);


        $lesson = new Lesson([
            'course_id' => $courseId,
            'title' => $validatedData['title'],
            'content' => $validatedData['content'] ?? null,
            'lesson_type' => $validatedData['lesson_type'],
            'video_url' => $validatedData['video_url'] ?? null,
            'homework_text' => $validatedData['homework_text'] ?? null,
            'homework_video_url' => $validatedData['homework_video_url'] ?? null,
        ]);

        // Обробка медіафайлів (фото, відео, документи)
        $mediaPaths = [];
        if ($request->hasFile('media_files')) {
            $mediaPaths = [];
            foreach ($request->file('media_files') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName(); // Додаємо timestamp
                $path = $file->storeAs('lessons/media', $filename, 'public');
                $mediaPaths[] = $path;
            }
            $lesson->media_files = json_encode($mediaPaths);
        }

        // Обробка файлів домашнього завдання

        $homeworkPaths = [];
        if ($request->hasFile('homework_file')) {
            $homeworkPaths = [];
            foreach ($request->file('homework_file') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('lessons/homework', $filename, 'public');
                $homeworkPaths[] = $path;
            }
            $lesson->homework_files = json_encode($homeworkPaths);
        }
        $lesson->save();

        return redirect()->route('admin.course.show', $courseId)
            ->with('success', 'Урок успішно створено!');
    }
}
