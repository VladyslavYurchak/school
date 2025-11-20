<?php

namespace App\Http\Requests\Admin\Teachers;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'user_id'      => 'required|exists:users,id',
            'lesson_price' => 'nullable|numeric|min:0',
            'note'         => 'nullable|string',
            'is_active'    => 'required|boolean',
            'group_lesson_price' => 'nullable|numeric|min:0',
            'trial_lesson_price' => 'nullable|numeric|min:0',
            'pair_lesson_price' => 'nullable|numeric|min:0'
        ];
    }
}
