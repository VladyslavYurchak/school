<?php

namespace App\Http\Requests\Admin\Students;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'birth_date' => 'nullable|date',
            'parent_contact' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'note' => 'nullable|string',
            'teacher_id' => 'nullable|exists:teachers,id',
            'subscription_id' => 'nullable|exists:subscription_templates,id',
        ];
    }
}
