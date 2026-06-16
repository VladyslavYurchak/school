<?php

namespace App\Http\Requests\Admin\SubscriptionTemplate;

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
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:individual,group,pair'],
            'lessons_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
