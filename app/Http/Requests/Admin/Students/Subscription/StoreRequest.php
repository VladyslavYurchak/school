<?php

namespace App\Http\Requests\Admin\Students\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:subscription,single'],

            'subscription_template_id' => [
                'nullable',
                'required_if:type,subscription',
                'exists:subscription_templates,id',
            ],

            'month' => [
                'nullable',
                'required_if:type,subscription',
                'date_format:Y-m',
            ],

            'price' => [
                'nullable',
                'required_if:type,single',
                'numeric',
                'min:0.01',
            ],

            'single_date' => ['nullable', 'date'],
        ];
    }
}
