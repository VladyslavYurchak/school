<?php

namespace App\Http\Requests\Admin\Course;

use App\Models\LessonExercise;
use App\Models\LessonExerciseItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LessonExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $type = $this->input('type');
        $answerMode = $this->input('answer_mode');
        $promptRules = ['required', 'string', 'max:255'];
        $answerRules = ['required', 'string', 'max:255'];

        if ($type === LessonExercise::TYPE_MATCHING) {
            $promptRules[] = 'max:255';
        } elseif ($type === LessonExercise::TYPE_FILL_BLANK) {
            $promptRules[] = function (string $attribute, mixed $value, \Closure $fail): void {
                if (substr_count((string) $value, '___') !== 1) {
                    $fail('Речення повинно містити рівно один пропуск ___.');
                }
            };
        } elseif ($type === LessonExercise::TYPE_WORD_ORDER) {
            $promptRules = ['nullable', 'string', 'max:255'];
            $answerRules[] = function (string $attribute, mixed $value, \Closure $fail): void {
                if (count(preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY)) < 2) {
                    $fail('Правильне речення повинно містити щонайменше два слова.');
                }
            };
        } elseif ($type === LessonExercise::TYPE_DICTATION) {
            $promptRules = ['nullable', 'string', 'max:255'];
        } elseif ($type === LessonExercise::TYPE_TRUE_FALSE) {
            $answerRules = ['required', Rule::in(['true', 'false'])];
        }

        if (
            $type === LessonExercise::TYPE_MATCHING
            || ($type === LessonExercise::TYPE_FILL_BLANK && $answerMode === LessonExercise::ANSWER_MODE_CHOICE)
        ) {
            $answerRules[] = 'distinct:ignore_case';
        }

        return [
            'type' => ['required', Rule::in(LessonExercise::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'answer_mode' => [
                Rule::requiredIf($type === LessonExercise::TYPE_FILL_BLANK),
                'nullable',
                Rule::in(LessonExercise::ANSWER_MODES),
            ],
            'is_active' => ['required', 'boolean'],
            'pairs' => [
                'required',
                'array',
                $this->usesSingleMinimum($type) ? 'min:1' : 'min:2',
                'max:30',
            ],
            'pairs.*.prompt' => [
                ...$promptRules,
                ...in_array($type, [
                    LessonExercise::TYPE_WORD_ORDER,
                    LessonExercise::TYPE_DICTATION,
                ], true) ? [] : ['distinct:ignore_case'],
            ],
            'pairs.*.answer' => $answerRules,
            'pairs.*.alternatives_text' => ['nullable', 'string', 'max:2000'],
            'pairs.*.explanation' => ['nullable', 'string', 'max:1000'],
            'pairs.*.existing_item_id' => ['nullable', 'integer'],
            'pairs.*.audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg,webm', 'max:12288'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== LessonExercise::TYPE_DICTATION) {
                return;
            }

            $exercise = $this->route('exercise');

            foreach ($this->input('pairs', []) as $index => $pair) {
                if ($this->file("pairs.$index.audio")) {
                    continue;
                }

                $existingItemId = $pair['existing_item_id'] ?? null;
                $hasExistingAudio = $exercise instanceof LessonExercise
                    && $existingItemId
                    && LessonExerciseItem::query()
                        ->where('lesson_exercise_id', $exercise->id)
                        ->whereKey($existingItemId)
                        ->whereNotNull('audio_path')
                        ->exists();

                if (!$hasExistingAudio) {
                    $validator->errors()->add(
                        "pairs.$index.audio",
                        'Додайте аудіофайл для кожного завдання диктанту.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'pairs.min' => $this->usesSingleMinimum($this->input('type'))
                ? 'Додайте щонайменше одне завдання.'
                : 'Додайте щонайменше дві пари.',
            'pairs.max' => 'В одній вправі може бути не більше 30 завдань.',
            'pairs.*.prompt.required' => 'Заповніть речення, твердження або фразу.',
            'pairs.*.prompt.distinct' => 'Завдання в межах вправи не повинні повторюватися.',
            'pairs.*.answer.required' => 'Заповніть правильну відповідь.',
            'pairs.*.answer.distinct' => 'Відповіді в межах вправи не повинні повторюватися.',
            'pairs.*.audio.mimes' => 'Аудіо повинно бути у форматі MP3, WAV, M4A, OGG або WEBM.',
            'pairs.*.audio.max' => 'Один аудіофайл не може перевищувати 12 МБ.',
            'answer_mode.required' => 'Оберіть спосіб відповіді учня.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $inputPairs = $this->input('pairs', []);
        $filePairs = $this->file('pairs', []);

        $pairs = collect(array_unique([...array_keys($inputPairs), ...array_keys($filePairs)]))
            ->sort()
            ->map(function ($index) use ($inputPairs, $filePairs): array {
                $pair = $inputPairs[$index] ?? [];

                return [
                    'prompt' => trim((string) ($pair['prompt'] ?? '')),
                    'answer' => trim((string) ($pair['answer'] ?? '')),
                    'alternatives_text' => trim((string) ($pair['alternatives_text'] ?? '')),
                    'explanation' => trim((string) ($pair['explanation'] ?? '')),
                    'existing_item_id' => $pair['existing_item_id'] ?? null,
                    'audio' => $filePairs[$index]['audio'] ?? null,
                ];
            })
            ->filter(fn ($pair) => $pair['prompt'] !== ''
                || $pair['answer'] !== ''
                || $pair['audio'] !== null)
            ->values()
            ->all();

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'answer_mode' => $this->input('answer_mode'),
            'pairs' => $pairs,
        ]);
    }

    private function usesSingleMinimum(?string $type): bool
    {
        return in_array($type, [
            LessonExercise::TYPE_WORD_ORDER,
            LessonExercise::TYPE_TRANSFORMATION,
            LessonExercise::TYPE_TRUE_FALSE,
            LessonExercise::TYPE_DICTATION,
        ], true);
    }
}
