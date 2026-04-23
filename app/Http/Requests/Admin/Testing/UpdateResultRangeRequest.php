<?php

namespace App\Http\Requests\Admin\Testing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResultRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'level_code' => ['nullable', 'string', 'max:20'],
            'min_score' => ['required', 'numeric', 'min:0'],
            'max_score' => ['required', 'numeric', 'gte:min_score'],
            'description' => ['nullable', 'string'],
            'recommendation_text' => ['nullable', 'string'],
        ];
    }
}
