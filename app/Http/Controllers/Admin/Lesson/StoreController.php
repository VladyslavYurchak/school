<?php

namespace App\Http\Controllers\Admin\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTest;
use App\Models\LessonTestOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(Request $request, $courseId)
    {
        // Валідація даних
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'lesson_type' => 'required|string',
            'video_url' => 'nullable|url',
            'homework_text' => 'nullable|string',
            'homework_video_url' => 'nullable|url',
            'media_files.*' => 'file|mimes:jpg,jpeg,png,mp3,mp4,mov,avi,wmv,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:20480',
            'homework_file.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,ppt,pptx|max:20480',
            'tests' => 'nullable|array',
            'tests.*.question' => 'required|string|max:255',
            'tests.*.answers' => [
                'required',
                'array',
                'min:3',
                'max:5',
                function ($attribute, $value, $fail) {
                    $nonEmpty = array_filter($value, function ($v) {
                        return trim($v) !== '';
                    });
                    if (count($nonEmpty) < 3) {
                        $fail("Кожен тест повинен містити хоча б три заповнені варіанти відповіді.");
                    }
                }
            ],
            'tests.*.correct_answer' => 'required_with:tests|integer|min:0|max:4',
        ], [
            'media_files.*.mimes' => 'Завантажте файл у форматах: jpg, jpeg, png, mp4, mov, avi, wmv, pdf, doc, docx, xls, xlsx, ppt, pptx, txt, zip, rar.',
            'homework_file.*.mimes' => 'Завантажте файл у форматах: jpg, jpeg, png, pdf, doc, docx, ppt, pptx.',
            'media_files.*.max' => 'Розмір файлу не повинен перевищувати 20MB.',
            'homework_file.*.max' => 'Розмір файлу не повинен перевищувати 20MB.',
            'tests.*.correct_answer.required_with' => 'Будь ласка, виберіть правильну відповідь.',
        ]);

        // Створення уроку в транзакції
        DB::transaction(function () use ($request, $validatedData, $courseId) {
            $lesson = Lesson::create([
                'course_id' => $courseId,
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'content' => $validatedData['content'] ?? null,
                'lesson_type' => $validatedData['lesson_type'],
                'video_url' => $validatedData['video_url'] ?? null,
                'homework_text' => $validatedData['homework_text'] ?? null,
                'homework_video_url' => $validatedData['homework_video_url'] ?? null,
            ]);

            // Обробка медіафайлів
            if ($request->hasFile('media_files')) {
                $mediaPaths = [];
                foreach ($request->file('media_files') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('lessons/media', $filename, 'public');
                    $mediaPaths[] = $path;
                }
                $lesson->media_files = json_encode($mediaPaths);
            }

            // Обробка файлів домашнього завдання
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

            // Обробка тестових завдань
            if (!empty($validatedData['tests'])) {
                foreach ($validatedData['tests'] as $test) {
                    $lessonTest = LessonTest::create([
                        'lesson_id' => $lesson->id,
                        'question' => $test['question'],
                        'is_multiple_choice' => count(array_filter($test['answers'], function ($a) {
                                return trim($a) !== '';
                            })) > 1,
                    ]);

                    foreach ($test['answers'] as $index => $answer) {
                        $trimmed = trim($answer);
                        if ($trimmed === '') {
                            continue;
                        }
                        LessonTestOption::create([
                            'lesson_test_id' => $lessonTest->id,
                            'option_text' => $trimmed,
                            'is_correct' => $index == $test['correct_answer'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.course.show', $courseId)
            ->with('success', 'Урок успішно створено!');
    }
}
