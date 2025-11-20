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
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id'  => ['required', 'integer', 'exists:groups,id'],
            'lesson_id' => ['required', 'integer', 'exists:planned_lessons,id'],

            // необов’язково: лише для додаткової звірки
            'date'      => ['sometimes', 'date'],
            'time'      => ['sometimes', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'group_id'  => 'група',
            'lesson_id' => 'заняття',
            'date'      => 'дата',
            'time'      => 'час',
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.required'  => 'Група є обовʼязковою.',
            'group_id.exists'    => 'Обрану групу не знайдено.',
            'lesson_id.required' => 'Заняття є обовʼязковим.',
            'lesson_id.exists'   => 'Обране заняття не знайдено.',
            'date.date'          => 'Невірний формат дати.',
            'time.date_format'   => 'Невірний формат часу (очікується H:i).',
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

            // 1) Урок має належати цій групі
            if ((int)$lesson->group_id !== $groupId) {
                $v->errors()->add('lesson_id', 'Заняття не належить вказаній групі.');
            }

            $lessonType = $lesson->lesson_type instanceof LessonType
                ? $lesson->lesson_type
                : LessonType::tryFrom((string) $lesson->lesson_type);

            // Аналогічно для статусу
            $status = $lesson->status instanceof LessonStatus
                ? $lesson->status
                : LessonStatus::tryFrom((string) $lesson->status);
            // ----------------------------------------


            if (!in_array($lessonType, [LessonType::Group, LessonType::Pair], true)) {
                $v->errors()->add('lesson_id', 'Заняття має бути групового або парного типу.');
            }

            // 3) Не скасовуємо вдруге
            if ($status === LessonStatus::Cancelled) {
                $v->errors()->add('lesson_id', 'Цей урок уже скасовано.');
            }

            // 4) Якщо користувач передав date/time — звіряємо зі start_date
            $date = $this->input('date');
            $time = $this->input('time');
            if ($date && $time) {
                try {
                    // Якщо в моделі є каст на immutable_datetime, тут уже Carbon
                    $start = $lesson->start_date instanceof \Carbon\CarbonInterface
                        ? $lesson->start_date
                        : Carbon::parse($lesson->start_date);

                    $reqDt = Carbon::parse($date.' '.$time);

                    if (!$start->equalTo($reqDt)) {
                        $v->errors()->add('date', 'Передані дата/час не збігаються з початком цього уроку.');
                    }
                } catch (\Throwable $e) {
                    // формат вже перевіряється rules(), ігноруємо
                }
            }
        });
    }
}
