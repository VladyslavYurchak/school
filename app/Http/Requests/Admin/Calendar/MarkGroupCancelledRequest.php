<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PlannedLesson;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use Carbon\Carbon;

class MarkGroupCancelledRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // при потребі додай перевірку доступу
    }

    // App/Http/Requests/Admin/Calendar/MarkGroupCancelledRequest.php
    public function rules(): array
    {
        return [
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'date'     => ['required', 'date'],          // наприклад: 2025-10-14
            'time'     => ['required', 'date_format:H:i'] // наприклад: 18:30
            // teacher_id не потрібен, якщо беремо з уроку
        ];
    }


    public function attributes(): array
    {
        return [
            'group_id'   => 'група',
            'lesson_id'  => 'заняття',
            'date'       => 'дата',
            'time'       => 'час',
            'teacher_id' => 'викладач',
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.required'   => 'Група є обовʼязковою.',
            'group_id.exists'     => 'Обрану групу не знайдено.',
            'lesson_id.required'  => 'Заняття є обовʼязковим.',
            'lesson_id.exists'    => 'Обране заняття не знайдено.',
            'date.required'       => 'Дата є обовʼязковою.',
            'date.date'           => 'Невірний формат дати.',
            'time.required'       => 'Час є обовʼязковим.',
            'time.date_format'    => 'Невірний формат часу (очікується H:i).',
            'teacher_id.required' => 'Викладач є обовʼязковим.',
            'teacher_id.exists'   => 'Обраного викладача не знайдено.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $lesson  = PlannedLesson::find((int)$this->input('lesson_id'));
            $groupId = (int)$this->input('group_id');

            if (!$lesson) {
                return; // базове правило exists спрацює
            }

            // 1) Переконаймося, що урок належить до цієї групи
            if ((int)$lesson->group_id !== $groupId) {
                $v->errors()->add('lesson_id', 'Заняття не належить вказаній групі.');
            }

            // 2) Переконаймося, що це груповий тип уроку
            if (
                $lesson->lesson_type !== null &&
                (string)$lesson->lesson_type !== LessonType::Group->value &&
                (string)$lesson->lesson_type !== LessonType::Pair->value
            ) {
                $v->errors()->add('lesson_id', 'Заняття має бути групового типу.');
            }

            // 3) Дозволяємо скасування навіть якщо Completed (тільки перевіримо, щоб не скасовано двічі)
            if ((string)$lesson->status === LessonStatus::Cancelled->value) {
                $v->errors()->add('lesson_id', 'Цей урок уже скасовано.');
            }

            // 4) Перевірка відповідності дати/часу
            try {
                $start = Carbon::parse($lesson->start_date);
                $reqDt = Carbon::parse($this->input('date') . ' ' . $this->input('time'));

                if (!$start->equalTo($reqDt)) {
                    $v->errors()->add('date', 'Передані дата/час не збігаються з початком цього уроку.');
                }
            } catch (\Throwable $e) {
                // формат уже перевірено rules
            }
        });
    }
}
