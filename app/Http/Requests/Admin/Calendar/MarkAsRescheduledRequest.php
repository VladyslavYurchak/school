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
        return true; // додай свою авторизацію за потреби
    }

    public function rules(): array
    {
        return [
            // id уроку йде у route-параметрі, тому тут правил для нього немає
            'initiator' => ['nullable', 'in:teacher,student,admin'],
            'new_date'  => ['required', 'date'],
            'new_time'  => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'initiator.in'     => 'Невірний ініціатор перенесення.',
            'new_date.required'=> 'Нова дата обовʼязкова.',
            'new_date.date'    => 'Невірний формат нової дати.',
            'new_time.required'=> 'Новий час обовʼязковий.',
            'new_time.date_format' => 'Невірний формат нового часу (очікується H:i).',
        ];
    }

    public function attributes(): array
    {
        return [
            'initiator' => 'ініціатор',
            'new_date'  => 'нова дата',
            'new_time'  => 'новий час',
        ];
    }

    protected function prepareForValidation(): void
    {
        // дефолт для ініціатора
        $this->merge([
            'initiator' => $this->input('initiator', 'teacher'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $lessonId = $this->route('id'); // з маршруту /{id}
            $lesson   = PlannedLesson::find($lessonId);

            if (!$lesson) {
                // Контролер все одно кине 404 через findOrFail,
                // але так помилку отримаємо раніше/акуратніше
                $v->errors()->add('lesson_id', 'Заняття не знайдено.');
                return;
            }

            // 1) Новий datetime ≠ старому
            try {
                $oldStart = Carbon::parse($lesson->start_date);
                $newStart = Carbon::parse($this->input('new_date') . ' ' . $this->input('new_time'));

                if ($oldStart->equalTo($newStart)) {
                    $v->errors()->add('new_date', 'Нова дата і час збігаються з поточними.');
                }
            } catch (\Throwable $e) {
                // базові правила вже дадуть помилку
            }


            // 3) (опційно) Якщо це індивідуальне перенесення, переконаймося, що тип — індивідуальний
            if ($lesson->group_id === null && $lesson->lesson_type !== null) {
                // не забороняємо явно, але можна увімкнути:
                // if ((string)$lesson->lesson_type !== LessonType::Individual->value) {
                //     $v->errors()->add('lesson_id', 'Перенесення очікується для індивідуального заняття.');
                // }
            }
        });
    }
}
