<?php

namespace App\Http\Requests\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Models\PlannedLesson;
use App\Models\Group;
use App\Models\SubscriptionTemplate;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start'         => ['required', 'date'],
            'end'           => ['nullable', 'date', 'after_or_equal:start'],
            'student_id'    => ['nullable', 'exists:students,id'],
            'group_id'      => ['nullable', 'exists:groups,id'],
            'teacher_id'    => ['nullable', 'exists:teachers,id'], // ← можна явно задати
            'duration'      => ['nullable', 'integer', 'min:15', 'max:180'],
            'notes'         => ['nullable', 'string'],
            'repeat_weekly' => ['nullable', 'boolean'],
            'lesson_type'   => ['required', Rule::in(['individual', 'group', 'pair', 'trial'])],
            'subscription_template_id' => 'nullable|exists:subscription_templates,id',
        ];
    }

    public function messages(): array
    {
        return [
            'start.required' => 'Початок заняття обовʼязковий.',
            'start.date'     => 'Невірний формат дати для початку.',
            'end.date'       => 'Невірний формат дати для завершення.',
            'end.after_or_equal' => 'Дата завершення має бути не раніше за початок.',
            'student_id.exists'  => 'Обраного учня не знайдено.',
            'group_id.exists'    => 'Обрану групу не знайдено.',
            'teacher_id.exists'  => 'Обраного викладача не знайдено.',
            'duration.integer'   => 'Тривалість має бути числом (хвилини).',
            'duration.min'       => 'Мінімальна тривалість — 15 хв.',
            'duration.max'       => 'Максимальна тривалість — 180 хв.',
            'repeat_weekly.boolean' => 'repeat_weekly має бути булевим значенням.',
            'lesson_type.required'  => 'Тип заняття обовʼязковий.',
            'lesson_type.in'        => 'Неприпустимий тип заняття.',
        ];
    }

    public function attributes(): array
    {
        return [
            'start'         => 'початок',
            'end'           => 'завершення',
            'student_id'    => 'учень',
            'group_id'      => 'група',
            'teacher_id'    => 'викладач',
            'duration'      => 'тривалість',
            'notes'         => 'нотатки',
            'repeat_weekly' => 'щотижневе повторення',
            'lesson_type'   => 'тип заняття',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'repeat_weekly' => filter_var($this->repeat_weekly, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'duration'      => $this->duration !== null ? (int) $this->duration : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $lessonType = $this->input('lesson_type');

            // 0) Узгодження типів: явний шаблон у формі ↔ тип заняття
            if ($this->filled('subscription_template_id')) {
                $tpl = SubscriptionTemplate::find($this->input('subscription_template_id'));
                if ($tpl && $tpl->type !== $lessonType) {
                    $v->errors()->add('subscription_template_id', 'Тип абонементу має відповідати типу заняття.');
                    return;
                }
            }

            // 0.1) Узгодження для ІНДИВІДУАЛЬНИХ/ПРОБНИХ уроків за student_id
            if (in_array($lessonType, ['individual','trial'], true) && $this->filled('student_id')) {
                $student = Student::with('subscriptionTemplate')->find($this->input('student_id'));
                if ($student && $student->subscriptionTemplate) {
                    $tplType = $student->subscriptionTemplate->type; // individual|group|pair
                    // trial за бажанням можна пропускати:
                    if ($lessonType !== 'trial' && $tplType !== $lessonType) {
                        $v->errors()->add('lesson_type', "Тип заняття має відповідати типу абонементу студента ({$tplType}).");
                        return;
                    }
                }
            }

            // 0.2) Узгодження для ГРУПОВИХ/ПАРНИХ уроків за group_id — перевіряємо КОЖНОГО студента групи
            if (in_array($lessonType, ['group','pair'], true) && $this->filled('group_id')) {
                $group = Group::with(['students.subscriptionTemplate'])->find($this->input('group_id'));
                if ($group) {
                    $mismatched = [];
                    foreach ($group->students as $st) {
                        $tpl = $st->subscriptionTemplate; // може бути null
                        if ($tpl && $tpl->type !== $lessonType) {
                            $mismatched[] = trim(($st->last_name ?? '').' '.($st->first_name ?? 'ID:'.$st->id));
                        }
                    }
                    if (!empty($mismatched)) {
                        $list = implode(', ', $mismatched);
                        $v->errors()->add(
                            'group_id',
                            "У групі є студенти з абонементом іншого типу: {$list}. Тип заняття: {$lessonType}."
                        );
                        return;
                    }
                }
            }

            // 1) Парсимо час
            try {
                $start = Carbon::parse($this->input('start'));
            } catch (\Throwable $e) {
                return;
            }

            // end = передане або start + duration (default 60)
            $endInput = $this->input('end');
            if ($endInput) {
                try {
                    $end = Carbon::parse($endInput);
                } catch (\Throwable $e) {
                    return;
                }
            } else {
                $minutes = (int) ($this->input('duration') ?? 60);
                $minutes = max(15, min($minutes, 180));
                $end = (clone $start)->addMinutes($minutes);
            }

            // 2) Визначаємо teacher_id: параметр → з групи → з авторизованого викладача
            $teacherId = $this->input('teacher_id');

            if (!$teacherId && $this->filled('group_id')) {
                $group = Group::find($this->input('group_id'));
                $teacherId = $group?->teacher_id;
                if ($this->filled('teacher_id') && $group && (int)$this->input('teacher_id') !== (int)$group->teacher_id) {
                    $v->errors()->add('teacher_id', 'Викладач не відповідає викладачу групи.');
                    return;
                }
            }

            if (!$teacherId && auth()->check()) {
                $teacherId = optional(auth()->user()->teacher)->id;
            }

            if (!$teacherId) {
                $v->errors()->add('teacher_id', 'Не вдалося визначити викладача для заняття.');
                return;
            }

            // 3) Анти-даблбукінг по викладачу
            $hasOverlap = PlannedLesson::query()
                ->where('teacher_id', $teacherId)
                ->whereNotIn('status', [LessonStatus::Cancelled->value, LessonStatus::Rescheduled->value]) // переконайтесь, що такі значення є в enum
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_date', '<', $end)
                        ->where('end_date',   '>', $start);
                })
                ->exists();

            if ($hasOverlap) {
                $v->errors()->add('start', 'Викладач уже має інше заняття у цей час.');
                return;
            }

            // 4) Якщо repeat_weekly=true — перевіряємо наступні 12 повторів
            if ($this->boolean('repeat_weekly')) {
                $checkWeeks = 12;
                for ($i = 1; $i <= $checkWeeks; $i++) {
                    $wStart = (clone $start)->addWeeks($i);
                    $wEnd   = (clone $end)->addWeeks($i);

                    $overlap = PlannedLesson::query()
                        ->where('teacher_id', $teacherId)
                        ->whereNotIn('status', [LessonStatus::Cancelled->value, LessonStatus::Rescheduled->value])
                        ->where(function ($q) use ($wStart, $wEnd) {
                            $q->where('start_date', '<', $wEnd)
                                ->where('end_date',   '>', $wStart);
                        })
                        ->exists();

                    if ($overlap) {
                        $v->errors()->add(
                            'repeat_weekly',
                            "Щотижневе повторення конфліктує на тижні №{$i} (початок {$wStart->format('Y-m-d H:i')})."
                        );
                        break;
                    }
                }
            }
        });
    }
}
