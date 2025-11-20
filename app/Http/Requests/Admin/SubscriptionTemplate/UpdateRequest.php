<?php

namespace App\Http\Requests\Admin\SubscriptionTemplate;

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
            'title' => 'required|string|max:255', // ← замість name
            'type' => 'required|in:individual,group,pair',
            'lessons_per_week' => 'required|integer|min:1|max:7',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ];
    }
}
