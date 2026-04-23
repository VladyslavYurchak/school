<?php

namespace App\Http\Requests\Admin\Teachers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Teacher;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Teacher|null $teacher */
        $teacher = $this->route('teacher');

        return [
            'user_id'      => 'required|exists:users,id',
            'first_name'          => ['required', 'string', 'max:255'],
            'last_name'           => ['required', 'string', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:20'],

            'lesson_price'        => ['nullable', 'numeric', 'min:0'],
            'note'                => ['nullable', 'string'],
            'is_active'           => ['required', 'boolean'],
            'group_lesson_price'  => ['nullable', 'numeric', 'min:0'],
            'trial_lesson_price'  => ['nullable', 'numeric', 'min:0'],
            'pair_lesson_price'   => ['nullable', 'numeric', 'min:0'],

            // Для сайту
            'public_photo'        => ['nullable', 'image', 'max:4096'],
            'public_bio'          => ['nullable', 'string'],
            'is_public'           => ['nullable', 'boolean'],
            'public_sort_order'   => ['nullable', 'integer', 'min:0'],
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
