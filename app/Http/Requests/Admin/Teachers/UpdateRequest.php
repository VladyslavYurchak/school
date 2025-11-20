<?php

namespace App\Http\Requests\Admin\Teachers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Teacher;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // or use a policy if you have one
    }

    public function rules(): array
    {
        /** @var Teacher|null $teacher */
        $teacher = $this->route('teacher'); // comes from {teacher} in your route

        return [
            'first_name'          => ['required','string','max:255'],
            'last_name'           => ['required','string','max:255'],
            'phone'               => ['nullable','string','max:20'],
            'email'               => [
                'nullable','email',
                Rule::unique('teachers', 'email')->ignore($teacher?->id),
            ],
            'lesson_price'        => ['nullable','numeric','min:0'],
            'note'                => ['nullable','string'],
            'is_active'           => ['required','boolean'],
            'group_lesson_price'  => ['nullable','numeric','min:0'],
            'trial_lesson_price'  => ['nullable','numeric','min:0'],
            'pair_lesson_price'   => ['nullable','numeric','min:0'],
        ];
    }

    // (optional) normalize booleans and numbers coming from forms
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => filter_var($this->boolean('is_active'), FILTER_VALIDATE_BOOL),
        ]);
    }
}
