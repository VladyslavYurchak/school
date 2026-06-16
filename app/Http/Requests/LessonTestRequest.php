<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => 'required|string|max:1000',
            'options' => 'required|array',
            'options.existing' => 'sometimes|array',
            'options.new' => 'sometimes|array',
            'options.existing.*.option_text' => 'nullable|string|max:1000',
            'options.existing.*.is_correct' => 'nullable|boolean',
            'options.new.*.option_text' => 'nullable|string|max:1000',
            'options.new.*.is_correct' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'options.required' => 'Варіанти відповідей обовʼязкові.',
            'options.array' => 'Варіанти відповідей мають бути списком.',
            'question.required' => 'Поле запитання обовʼязкове.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $newOptions = array_filter(
            $this->input('options.new', []),
            fn ($option) => trim((string) ($option['option_text'] ?? '')) !== ''
        );

        $this->merge([
            'options' => [
                'existing' => $this->input('options.existing', []),
                'new' => array_values($newOptions),
            ],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('options.existing', []) as $option) {
                if (trim((string) ($option['option_text'] ?? '')) === '') {
                    $validator->errors()->add(
                        'options',
                        'Кожна наявна відповідь повинна мати текст. Видаліть відповідь, якщо вона більше не потрібна.'
                    );
                    break;
                }
            }

            $filledOptions = $this->filledOptions();

            if (count($filledOptions) < 3) {
                $validator->errors()->add('options', 'Додайте щонайменше 3 заповнені варіанти відповіді.');
            }

            if (count($filledOptions) > 5) {
                $validator->errors()->add('options', 'Додайте не більше 5 варіантів відповіді.');
            }

            if ($this->correctAnswersCount() < 1) {
                $validator->errors()->add('options', 'Позначте щонайменше одну правильну відповідь.');
            }
        });
    }

    public function filledOptions(): array
    {
        $options = [];

        foreach (['existing', 'new'] as $group) {
            foreach ($this->input("options.{$group}", []) as $key => $option) {
                $text = trim((string) ($option['option_text'] ?? ''));

                if ($text === '') {
                    continue;
                }

                $options[] = [
                    'group' => $group,
                    'key' => $key,
                    'option_text' => $text,
                    'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            }
        }

        return $options;
    }

    public function correctAnswersCount(): int
    {
        return count(array_filter(
            $this->filledOptions(),
            fn (array $option) => $option['is_correct']
        ));
    }

    public function isMultipleChoice(): bool
    {
        return $this->correctAnswersCount() > 1;
    }
}
