<?php

namespace App\Http\Requests\Admin\Testing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $test = $this->route('test');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('testing_tests', 'slug')->ignore($test->id)],
            'language_code' => ['required', 'string', 'max:10'],
            'description' => ['nullable', 'string'],
            'intro_text' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'randomize_questions' => ['nullable', 'boolean'],
            'show_result_immediately' => ['nullable', 'boolean'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
