<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PlannedLesson;
use App\Enums\LessonType;
use Carbon\Carbon;

class MarkGroupRescheduledRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // додай перевірку ролей за потреби
    }

    public function rules(): array
    {
        return [
            'group_id'  => ['required', 'integer', 'exists:groups,id'],
            'lesson_id' => ['required', 'integer', 'exists:planned_lessons,id'],
            'new_date'  => ['required', 'date'],
            'new_time'  => ['required', 'date_format:H:i'],
            // 'date' / 'time' більше не потрібні — працюємо по lesson_id
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.required'  => 'Група є обовʼязковою.',
            'group_id.exists'    => 'Обрану групу не знайдено.',
            'lesson_id.required' => 'Заняття є обовʼязковим.',
            'lesson_id.exists'   => 'Обране заняття не знайдено.',
            'new_date.required'  => 'Нова дата обовʼязкова.',
            'new_date.date'      => 'Невірний формат нової дати.',
            'new_time.required'  => 'Новий час обовʼязковий.',
            'new_time.date_format' => 'Невірний формат нового часу (очікується H:i).',
        ];
    }

    public function attributes(): array
    {
        return [
            'group_id'  => 'група',
            'lesson_id' => 'заняття',
            'new_date'  => 'нова дата',
            'new_time'  => 'новий час',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $lessonId = (int) $this->input('lesson_id');
            $groupId  = (int) $this->input('group_id');

            $lesson = PlannedLesson::find($lessonId);
            if (!$lesson) {
                return; // базові правила вже додадуть помилку
            }

            // 1) урок має належати цій групі
            if ((int)$lesson->group_id !== $groupId) {
                $v->errors()->add('lesson_id', 'Заняття не належить вказаній групі.');
            }

            // 2) тип — лише group або pair
            if (
                $lesson->lesson_type !== null &&
                (string)$lesson->lesson_type !== LessonType::Group->value &&
                (string)$lesson->lesson_type !== LessonType::Pair->value
            ) {
                $v->errors()->add('lesson_id', 'Заняття має бути групового або парного типу.');
            }

            // 3) новий datetime відрізняється від старого
            try {
                $oldStart = Carbon::parse($lesson->start_date);
                $newStart = Carbon::parse($this->input('new_date') . ' ' . $this->input('new_time'));

                if ($oldStart->equalTo($newStart)) {
                    $v->errors()->add('new_date', 'Нова дата і час співпадають з поточними.');
                }

                // 4) немає конфлікту з іншим уроком цієї групи у новий слот
                $conflict = PlannedLesson::query()
                    ->where('group_id', $groupId)
                    ->where('start_date', $newStart)
                    ->where('id', '!=', $lessonId)
                    ->exists();

                if ($conflict) {
                    $v->errors()->add('new_date', 'Для цієї групи вже існує інше заняття у вказаний час.');
                }
            } catch (\Throwable $e) {
                // формат уже перевірено в rules
            }
        });
    }
}
