<?php

namespace App\Http\Requests\Admin\Testing;

use Illuminate\Foundation\Http\FormRequest;

class StoreOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option_text' => ['required', 'string'],
            'option_value' => ['nullable', 'string', 'max:255'],
            'is_correct' => ['nullable', 'boolean'],
            'points' => ['nullable', 'numeric'],
            'explanation' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_correct' => $this->boolean('is_correct'),
        ]);
    }
}
