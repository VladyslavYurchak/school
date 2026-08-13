<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasActiveTeacherProfile() === true;
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
