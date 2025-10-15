<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // якщо потрібні додаткові перевірки доступу — додай тут
        return true;
    }

    public function rules(): array
    {
        return [
            'start'         => ['required', 'date'],
            'end'           => ['nullable', 'date', 'after_or_equal:start'],
            'student_id'    => ['nullable', 'exists:students,id'],
            'group_id'      => ['nullable', 'exists:groups,id'],
            'duration'      => ['nullable', 'integer', 'min:15', 'max:180'],
            'notes'         => ['nullable', 'string'],
            'repeat_weekly' => ['nullable', 'boolean'],
            'lesson_type'   => ['required', Rule::in(['individual', 'group', 'pair', 'trial'])],
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
}
