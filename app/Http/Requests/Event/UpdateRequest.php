<?php

namespace App\Http\Requests\Event;

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
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'cropped_image' => ['nullable', 'string', 'max:16777216', 'regex:/^data:image\/(?:jpeg|png|webp);base64,/'],
            'start_date' => ['required', 'date'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
