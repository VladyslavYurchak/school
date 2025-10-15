<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PlannedLesson;
use App\Enums\LessonType;

class MarkGroupRescheduledRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Якщо треба обмежити за роллю — додай перевірку тут
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'lesson_id' => ['required', 'exists:planned_lessons,id'],
            'new_date' => ['required', 'date'],
            'new_time' => ['required', 'date_format:H:i'],
            'date' => ['required', 'date'],            // стара дата (для чистки LessonLog)
            'time' => ['required', 'date_format:H:i'], // старий час (для чистки LessonLog)
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.required' => 'Група є обовʼязковою.',
            'group_id.exists' => 'Обрану групу не знайдено.',
            'lesson_id.required' => 'Заняття є обовʼязковим.',
            'lesson_id.exists' => 'Обране заняття не знайдено.',
            'new_date.required' => 'Нова дата обовʼязкова.',
            'new_date.date' => 'Невірний формат нової дати.',
            'new_time.required' => 'Новий час обовʼязковий.',
            'new_time.date_format' => 'Невірний формат нового часу (очікується H:i).',
            'date.required' => 'Поточна дата обовʼязкова.',
            'date.date' => 'Невірний формат поточної дати.',
            'time.required' => 'Поточний час обовʼязковий.',
            'time.date_format' => 'Невірний формат поточного часу (очікується H:i).',
        ];
    }

    public function attributes(): array
    {
        return [
            'group_id' => 'група',
            'lesson_id' => 'заняття',
            'new_date' => 'нова дата',
            'new_time' => 'новий час',
            'date' => 'поточна дата',
            'time' => 'поточний час',
        ];
    }

    protected function prepareForValidation(): void
    {
        // нічого особливого, але місце є, якщо захочеш нормалізувати вхідні дані
    }

    /**
     * Додаткові перевірки зв’язків і логіки:
     * - lesson має належати цій групі
     * - lesson має бути групового типу
     * - новий datetime не збігається зі старим
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $lessonId = $this->input('lesson_id');
            $groupId = $this->input('group_id');

            $lesson = PlannedLesson::find($lessonId);
            if (!$lesson) {
                return; // exists уже згенерував помилку
            }

            // 1) Перевірка відповідності групі
            if ((int)$lesson->group_id !== (int)$groupId) {
                $v->errors()->add('lesson_id', 'Заняття не належить вказаній групі.');
            }

            // 2) Перевірка типу заняття
            if (
                $lesson->lesson_type !== null &&
                (string)$lesson->lesson_type !== LessonType::Group->value &&
                (string)$lesson->lesson_type !== LessonType::Pair->value
            ) {
                $v->errors()->add('lesson_id', 'Заняття має бути групового типу.');
            }


            // 3) Новий datetime не повинен співпадати зі старим
            try {
                $oldStart = \Carbon\Carbon::parse($lesson->start_date);
                $newStart = \Carbon\Carbon::parse($this->input('new_date') . ' ' . $this->input('new_time'));

                if ($oldStart->equalTo($newStart)) {
                    $v->errors()->add('new_date', 'Нова дата і час співпадають з поточними.');
                }
            } catch (\Throwable $e) {
                // якщо парсинг впаде — базові правила вже згенерують помилки
            }
        });
    }
}
