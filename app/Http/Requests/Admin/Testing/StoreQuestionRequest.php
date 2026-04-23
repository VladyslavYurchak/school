<?php

namespace App\Http\Requests\Admin\Testing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'exists:testing_sections,id'],
            'type' => ['required', Rule::in([
                'single_choice',
                'multiple_choice',
                'short_text',
                'long_text',
                'true_false',
            ])],
            'title' => ['nullable', 'string', 'max:255'],
            'question_text' => ['required', 'string'],
            'helper_text' => ['nullable', 'string'],
            'content_before' => ['nullable', 'string'],
            'content_after' => ['nullable', 'string'],
            'default_correct_points' => ['required', 'numeric', 'min:0'],
            'default_incorrect_points' => ['nullable', 'numeric'],
            'difficulty_level' => ['required', Rule::in(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_required' => $this->boolean('is_required'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
