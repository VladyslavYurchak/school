<?php

namespace App\Http\Requests\Admin\SocialPublishing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePublicationRequest extends FormRequest
{
    private const PLATFORMS = ['facebook', 'instagram', 'tiktok'];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2200'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'distinct', Rule::in(self::PLATFORMS)],
            'media_file' => [
                'nullable',
                'file',
                'max:102400',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm',
            ],
            'remove_media' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'platforms.required' => 'Оберіть хоча б одну соцмережу.',
            'platforms.min' => 'Оберіть хоча б одну соцмережу.',
            'caption.max' => 'Текст публікації не може перевищувати 2200 символів.',
            'media_file.max' => 'Файл не може бути більшим за 100 МБ.',
            'media_file.mimetypes' => 'Дозволені JPG, PNG, WEBP, MP4, MOV або WEBM.',
        ];
    }
}
