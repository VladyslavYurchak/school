<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PlannedLesson;
use App\Enums\LessonType;
use App\Enums\LessonStatus;
use Carbon\Carbon;

class MarkAsRescheduledRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'initiator' => ['nullable', 'in:teacher,student,admin'],
            'new_date'  => ['required', 'date'],
            'new_time'  => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'initiator.in'         => 'Невірний ініціатор перенесення.',
            'new_date.required'    => 'Нова дата обовʼязкова.',
            'new_date.date'        => 'Невірний формат нової дати.',
            'new_time.required'    => 'Новий час обовʼязковий.',
            'new_time.date_format' => 'Невірний формат нового часу (очікується H:i).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'initiator' => $this->input('initiator', 'teacher'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $lessonId = (int) $this->route('id');
            $lesson   = PlannedLesson::find($lessonId);

            if (!$lesson) {
                $v->errors()->add('lesson_id', 'Заняття не знайдено.');
                return;
            }

            // цей реквест/ендпойнт — тільки для індивідуальних/пробних
            if ($lesson->group_id !== null ||
                ($lesson->lesson_type !== null &&
                    in_array((string)$lesson->lesson_type, [LessonType::Group->value, LessonType::Pair->value], true))) {
                $v->errors()->add('lesson_id', 'Цей ендпойнт лише для індивідуальних/пробних занять.');
            }

            // новий інтервал = newStart ... newEnd (newEnd рахуємо з тривалості поточного уроку)
            try {
                $oldStart = Carbon::parse($lesson->start_date);
                $oldEnd   = Carbon::parse($lesson->end_date);

                $durMin = $lesson->duration ?? $oldStart->diffInMinutes($oldEnd) ?: 60;
                $durMin = max(15, $durMin);

                $newStart = Carbon::parse($this->input('new_date').' '.$this->input('new_time'));
                $newEnd   = (clone $newStart)->addMinutes($durMin);

                if ($oldStart->equalTo($newStart)) {
                    $v->errors()->add('new_date', 'Нова дата і час збігаються з поточними.');
                }

                // ПЕРЕВІРКА ПЕРЕТИНУ ІНТЕРВАЛІВ:
                // існує конфлікт, якщо existing.start < newEnd && existing.end > newStart
                $teacherBusy = PlannedLesson::query()
                    ->where('teacher_id', $lesson->teacher_id)
                    ->where('id', '!=', $lesson->id)
                    ->whereNotIn('status', [LessonStatus::Cancelled->value, LessonStatus::Rescheduled->value])
                    ->where(function ($q) use ($newStart, $newEnd) {
                        $q->where('start_date', '<', $newEnd)
                            ->where('end_date',   '>', $newStart);
                    })
                    ->exists();

                if ($teacherBusy) {
                    $v->errors()->add('new_time', 'У викладача вже є інше заняття, що перетинається з цим часом.');
                }

                if ($lesson->student_id) {
                    $studentBusy = PlannedLesson::query()
                        ->where('student_id', $lesson->student_id)
                        ->where('id', '!=', $lesson->id)
                        ->whereNotIn('status', [LessonStatus::Cancelled->value, LessonStatus::Rescheduled->value])
                        ->where(function ($q) use ($newStart, $newEnd) {
                            $q->where('start_date', '<', $newEnd)
                                ->where('end_date',   '>', $newStart);
                        })
                        ->exists();

                    if ($studentBusy) {
                        $v->errors()->add('new_time', 'В учня вже є інше заняття, що перетинається з цим часом.');
                    }
                }
            } catch (\Throwable $e) {
                // формат уже перевірено в rules
            }
        });
    }
}
