<?php

namespace App\Http\Requests\Admin\Testing;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'instruction_text' => ['nullable', 'string'],
            'media_type' => ['required', 'string', 'max:50'],
            'media_url' => ['nullable', 'string', 'max:1000'],
            'media_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'media_title' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
