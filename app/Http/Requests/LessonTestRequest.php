<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

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
        ];
    }

    public function messages(): array
    {
        return [
            'options.required' => 'Поле варіантів обов’язкове.',
            'options.array' => 'Варіанти мають бути у вигляді списку.',
            'question.required' => 'Поле запитання є обов’язковим.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Очищаємо options.new від пустих
        $filtered = array_filter(
            $this->input('options.new', []),
            fn($option) => !empty($option['option_text'])
        );

        $this->merge([
            'options' => [
                'existing' => $this->input('options.existing', []),
                'new' => array_values($filtered),
            ],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allOptions = $this->input('options.existing', []) + $this->input('options.new', []);

            $filledOptions = array_filter($allOptions, function ($option) {
                return !empty($option['option_text']);
            });

            if (count($filledOptions) < 3) {
                $validator->errors()->add('options', 'Потрібно ввести щонайменше 3 заповнені варіанти відповіді.');
            }

            if (count($filledOptions) > 5) {
                $validator->errors()->add('options', 'Можна додати максимум 5 варіантів відповіді.');
            }

            $hasCorrect = false;
            foreach ($filledOptions as $option) {
                if (!empty($option['is_correct'])) {
                    $hasCorrect = true;
                    break;
                }
            }

            if (!$hasCorrect) {
                $validator->errors()->add('options', 'Має бути хоча б одна правильна відповідь.');
            }
        });
    }
}
