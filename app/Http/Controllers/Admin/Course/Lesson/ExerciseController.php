<?php

namespace App\Http\Controllers\Admin\Course\Lesson;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\LessonExerciseRequest;
use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\LessonExerciseItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ExerciseController extends Controller
{
    public function index(Lesson $lesson): View
    {
        $lesson->load('course');
        $exercises = $lesson->exercises()->with('items')->get();

        return view('admin.course.lesson.exercises.index', compact('lesson', 'exercises'));
    }

    public function create(Lesson $lesson): View
    {
        $lesson->load('course');
        $type = in_array(request()->query('type'), LessonExercise::TYPES, true)
            ? request()->query('type')
            : LessonExercise::TYPE_MATCHING;

        return view('admin.course.lesson.exercises.create', compact('lesson', 'type'));
    }

    public function store(LessonExerciseRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($lesson, $data): void {
            $exercise = $lesson->exercises()->create([
                'type' => $data['type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'settings' => $this->exerciseSettings($data),
                'is_active' => $data['is_active'],
                'position' => ((int) $lesson->exercises()->max('position')) + 1,
            ]);

            $this->replaceItems($exercise, $data['pairs']);
        });

        return redirect()
            ->route('admin.course.lesson.exercises.index', $lesson)
            ->with('success', 'Вправу додано до уроку.');
    }

    public function edit(Lesson $lesson, LessonExercise $exercise): View
    {
        $this->ensureExerciseBelongsToLesson($lesson, $exercise);
        $lesson->load('course');
        $exercise->load('items');

        return view('admin.course.lesson.exercises.edit', compact('lesson', 'exercise'));
    }

    public function update(
        LessonExerciseRequest $request,
        Lesson $lesson,
        LessonExercise $exercise
    ): RedirectResponse {
        $this->ensureExerciseBelongsToLesson($lesson, $exercise);
        $data = $request->validated();

        DB::transaction(function () use ($exercise, $data): void {
            $exercise->update([
                'type' => $data['type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'settings' => $this->exerciseSettings($data),
                'is_active' => $data['is_active'],
            ]);

            $this->replaceItems($exercise, $data['pairs']);
        });

        return redirect()
            ->route('admin.course.lesson.exercises.index', $lesson)
            ->with('success', 'Вправу оновлено.');
    }

    public function toggle(Lesson $lesson, LessonExercise $exercise): RedirectResponse
    {
        $this->ensureExerciseBelongsToLesson($lesson, $exercise);
        $exercise->update(['is_active' => !$exercise->is_active]);

        return back()->with('success', $exercise->is_active ? 'Вправу показано учням.' : 'Вправу приховано.');
    }

    public function destroy(Lesson $lesson, LessonExercise $exercise): RedirectResponse
    {
        $this->ensureExerciseBelongsToLesson($lesson, $exercise);

        $exercise->items()
            ->whereNotNull('audio_path')
            ->pluck('audio_path')
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $exercise->delete();
        $this->normalizePositions($lesson);

        return back()->with('success', 'Вправу видалено.');
    }

    public function updateOrder(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'exercises' => ['required', 'array'],
            'exercises.*' => [
                'required',
                'integer',
                Rule::exists('lesson_exercises', 'id')->where('lesson_id', $lesson->id),
            ],
        ]);

        $currentIds = $lesson->exercises()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $submittedIds = collect($data['exercises'])->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($currentIds !== $submittedIds) {
            throw ValidationException::withMessages([
                'exercises' => 'Порядок має містити всі вправи цього уроку.',
            ]);
        }

        foreach ($data['exercises'] as $index => $exerciseId) {
            LessonExercise::query()
                ->where('lesson_id', $lesson->id)
                ->whereKey($exerciseId)
                ->update(['position' => $index + 1]);
        }

        return response()->json(['saved' => true]);
    }

    private function replaceItems(LessonExercise $exercise, array $pairs): void
    {
        $oldItems = $exercise->items()->get()->keyBy('id');
        $oldAudioPaths = $oldItems->pluck('audio_path')->filter()->values();
        $newAudioPaths = collect();
        $uploadedAudioPaths = collect();

        try {
            $rows = collect($pairs)->map(function ($pair, $index) use (
                $exercise,
                $oldItems,
                $newAudioPaths,
                $uploadedAudioPaths
            ): array {
                $existingItem = $oldItems->get((int) ($pair['existing_item_id'] ?? 0));
                $audioPath = $existingItem?->audio_path;

                if (($pair['audio'] ?? null) instanceof UploadedFile) {
                    $audioPath = $pair['audio']->store('lesson-exercises/audio', 'public');
                    $uploadedAudioPaths->push($audioPath);
                }

                if ($exercise->type !== LessonExercise::TYPE_DICTATION) {
                    $audioPath = null;
                }

                if ($audioPath) {
                    $newAudioPaths->push($audioPath);
                }

                return [
                    'prompt' => $pair['prompt'],
                    'answer' => $pair['answer'],
                    'settings' => $this->itemSettings($exercise->type, $pair),
                    'audio_path' => $audioPath,
                    'position' => $index + 1,
                ];
            })->all();

            $exercise->items()->delete();
            $exercise->items()->createMany($rows);
        } catch (Throwable $exception) {
            $uploadedAudioPaths->each(
                fn (string $path) => Storage::disk('public')->delete($path)
            );

            throw $exception;
        }

        $oldAudioPaths
            ->diff($newAudioPaths)
            ->each(fn (string $path) => Storage::disk('public')->delete($path));
    }

    private function exerciseSettings(array $data): ?array
    {
        if ($data['type'] !== LessonExercise::TYPE_FILL_BLANK) {
            return null;
        }

        return [
            'answer_mode' => $data['answer_mode'],
        ];
    }

    private function itemSettings(string $type, array $pair): ?array
    {
        if (in_array($type, [
            LessonExercise::TYPE_TRANSFORMATION,
            LessonExercise::TYPE_DICTATION,
        ], true)) {
            $acceptedAnswers = collect([
                $pair['answer'],
                ...preg_split('/\R/u', (string) ($pair['alternatives_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY),
            ])
                ->map(fn (string $answer) => trim($answer))
                ->filter()
                ->unique(fn (string $answer) => mb_strtolower($answer))
                ->values()
                ->all();

            return ['accepted_answers' => $acceptedAnswers];
        }

        if ($type === LessonExercise::TYPE_TRUE_FALSE) {
            return [
                'explanation' => $pair['explanation'] ?: null,
            ];
        }

        return null;
    }

    private function ensureExerciseBelongsToLesson(Lesson $lesson, LessonExercise $exercise): void
    {
        abort_unless($exercise->lesson_id === $lesson->id, 404);
    }

    private function normalizePositions(Lesson $lesson): void
    {
        $lesson->exercises()->get()->each(function (LessonExercise $exercise, int $index): void {
            $exercise->update(['position' => $index + 1]);
        });
    }
}
