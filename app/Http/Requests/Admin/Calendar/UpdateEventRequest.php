<?php

namespace App\Http\Requests\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Models\PlannedLesson;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // якщо потрібно обмежити доступ — можеш додати перевірку тут
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'time'         => ['required'],
            'duration'     => ['nullable', 'integer', 'min:15', 'max:180'],
            'notes'        => ['nullable', 'string'],
            'student_id'   => ['nullable', 'exists:students,id'],
            'group_id'     => ['nullable', 'exists:groups,id'],
            'lesson_type'  => ['required', Rule::in(['individual', 'group', 'pair', 'trial'])],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Назва заняття обовʼязкова.',
            'title.string'         => 'Назва повинна бути рядком.',
            'title.max'            => 'Назва не може перевищувати 255 символів.',
            'date.required'        => 'Дата обовʼязкова.',
            'date.date'            => 'Невірний формат дати.',
            'time.required'        => 'Час обовʼязковий.',
            'duration.integer'     => 'Тривалість має бути числом (у хвилинах).',
            'duration.min'         => 'Мінімальна тривалість — 15 хвилин.',
            'duration.max'         => 'Максимальна тривалість — 180 хвилин.',
            'notes.string'         => 'Нотатки повинні бути рядком.',
            'student_id.exists'    => 'Обраного учня не знайдено.',
            'group_id.exists'      => 'Обрану групу не знайдено.',
            'lesson_type.required' => 'Тип заняття обовʼязковий.',
            'lesson_type.in'       => 'Неприпустимий тип заняття.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'       => 'назва',
            'date'        => 'дата',
            'time'        => 'час',
            'duration'    => 'тривалість',
            'notes'       => 'нотатки',
            'student_id'  => 'учень',
            'group_id'    => 'група',
            'lesson_type' => 'тип заняття',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'duration' => $this->duration !== null ? (int) $this->duration : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $teacherId = optional($this->user()?->teacher)->id;

            if (!$teacherId) {
                return;
            }

            try {
                $start = Carbon::parse($this->input('date').' '.$this->input('time'));
            } catch (\Throwable) {
                return;
            }

            $duration = (int) ($this->input('duration') ?? 60);
            $duration = max(15, min($duration, 180));
            $end = $start->copy()->addMinutes($duration);

            $lessonId = (int) $this->route('id');

            $hasOverlap = PlannedLesson::query()
                ->where('teacher_id', $teacherId)
                ->where('id', '!=', $lessonId)
                ->whereNotIn('status', [
                    LessonStatus::Cancelled->value,
                    LessonStatus::Rescheduled->value,
                ])
                ->where(function ($query) use ($start, $end) {
                    $query
                        ->where('start_date', '<', $end)
                        ->where('end_date', '>', $start);
                })
                ->exists();

            if ($hasOverlap) {
                $v->errors()->add('date', 'Teacher already has another lesson at this time.');
            }
        });
    }
}
