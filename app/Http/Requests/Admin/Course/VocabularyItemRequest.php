<?php

namespace App\Http\Requests\Admin\Course;

use Illuminate\Foundation\Http\FormRequest;

class VocabularyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'term' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:2000'],
            'transcription' => ['nullable', 'string', 'max:255'],
            'part_of_speech' => ['nullable', 'string', 'max:100'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'example' => ['nullable', 'string', 'max:5000'],
            'example_translation' => ['nullable', 'string', 'max:5000'],
            'is_required' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'term' => trim((string) $this->input('term')),
            'translation' => trim((string) $this->input('translation')),
            'is_required' => $this->boolean('is_required'),
        ]);
    }
}
