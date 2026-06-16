<?php

namespace App\Http\Requests\Admin\Course;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'language_id' => 'required|exists:languages,id',
            'price' => 'nullable|numeric|min:0',
            'is_published' => 'boolean',
        ];
    }
}
