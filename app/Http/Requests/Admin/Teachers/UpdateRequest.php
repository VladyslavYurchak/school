<?php

namespace App\Http\Requests\Admin\Teachers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::in([(string) $this->route('teacher')->user_id]),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2048'],

            'lesson_price' => ['nullable', 'numeric', 'min:0'],
            'group_lesson_price' => ['nullable', 'numeric', 'min:0'],
            'trial_lesson_price' => ['nullable', 'numeric', 'min:0'],
            'pair_lesson_price' => ['nullable', 'numeric', 'min:0'],

            'note' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],

            'public_photo' => ['nullable', 'image', 'max:4096'],
            'public_position' => ['nullable', 'string', 'max:255'],
            'public_bio' => ['nullable', 'string'],
            'public_details' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
            'public_sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_public' => $this->boolean('is_public'),
        ]);
    }
}
