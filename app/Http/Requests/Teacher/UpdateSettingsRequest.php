<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true
            && (bool) $this->user()?->teacher?->is_active;
    }

    public function rules(): array
    {
        return [
            'meeting_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'meeting_url' => 'посилання на Zoom',
        ];
    }
}
