<?php

namespace App\Http\Requests\Admin\Students\Subscription;

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
            'type' => 'required|in:subscription,single',
            'subscription_template_id' => 'nullable|exists:subscription_templates,id',
            'month' => 'nullable|date_format:Y-m',
            'price' => 'nullable|numeric|min:0'
        ];
    }
}
