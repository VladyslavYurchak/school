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
        return true; // TODO: додай перевірку ролей за потреби
    }

    public function rules(): array
    {
        return [
            'group_id'  => ['required', 'integer', 'exists:groups,id'],
            'lesson_id' => ['required', 'integer', 'exists:planned_lessons,id'],
            'new_date'  => ['required', 'date_format:Y-m-d'],
            'new_time'  => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.required'     => 'Група є обовʼязковою.',
            'group_id.exists'       => 'Обрану групу не знайдено.',
            'lesson_id.required'    => 'Заняття є обовʼязковим.',
            'lesson_id.exists'      => 'Обране заняття не знайдено.',
            'new_date.required'     => 'Нова дата обовʼязкова.',
            'new_date.date_format'  => 'Невірний формат нової дати (очікується Y-m-d).',
            'new_time.required'     => 'Новий час обовʼязковий.',
            'new_time.date_format'  => 'Невірний формат нового часу (очікується H:i).',
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
                // Базове правило exists уже спрацює, тут просто зупиняємося
                return;
            }

            // 1) урок має належати цій групі
            if ((int) $lesson->group_id !== $groupId) {
                $v->errors()->add('lesson_id', 'Заняття не належить вказаній групі.');
            }

            // 2) тип — лише group або pair (коректно і для enum-касту, і для рядка)
            $lt = $lesson->lesson_type;
            $isValidType = false;

            if ($lt instanceof LessonType) {
                $isValidType = in_array($lt, [LessonType::Group, LessonType::Pair], true);
            } else {
                // якщо з БД приходить рядок/int без касту
                $isValidType = ($lt === LessonType::Group->value) || ($lt === LessonType::Pair->value);
            }

            if (!$isValidType) {
                $v->errors()->add('lesson_id', 'Заняття має бути групового або парного типу.');
            }

            // 3) новий datetime відрізняється від старого + 4) немає конфлікту
            try {
                // старий початок без секунд
                $oldStart = Carbon::parse($lesson->start_date)->second(0);

                // новий початок у тайзоні додатку (або підстав свій)
                $tz = config('app.timezone');
                $newStart = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $this->input('new_date') . ' ' . $this->input('new_time'),
                    $tz
                )->second(0);

                if ($oldStart->equalTo($newStart)) {
                    $v->errors()->add('new_date', 'Нова дата і час співпадають з поточними.');
                    // якщо вже співпадають — далі перевіряти конфлікти не потрібно
                    return;
                }

                // тривалість для розрахунку end; за потреби дістань з моделі/конфіга
                $durationMinutes = (int) ($lesson->duration_minutes ?? 60);
                $newEnd = $newStart->copy()->addMinutes($durationMinutes);

                // перевірка перетину інтервалів у межах цієї ж групи
                $conflict = PlannedLesson::query()
                    ->where('group_id', $groupId)
                    ->where('id', '!=', $lessonId)
                    // A.start < B.end && A.end > B.start
                    ->where(function ($q) use ($newStart, $newEnd) {
                        $q->where('start_date', '<', $newEnd)
                            ->where('end_date',   '>', $newStart);
                    })
                    ->exists();

                if ($conflict) {
                    $v->errors()->add('new_date', 'Для цієї групи вже існує інше заняття у вказаний час.');
                }
            } catch (\Throwable $e) {
                // формат уже перевіряється в rules(); тут тихо ігноруємо
            }
        });
    }
}
