<?php

namespace App\Http\Requests\Admin\Course;

use App\Models\LessonContentBlock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonContentBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');
        $creating = !$this->route('block');
        $mediaRequired = $creating && in_array($type, [
            LessonContentBlock::TYPE_AUDIO,
            LessonContentBlock::TYPE_IMAGE,
            LessonContentBlock::TYPE_PDF,
        ], true);

        $mediaRules = [Rule::requiredIf($mediaRequired), 'nullable', 'file'];

        if ($type === LessonContentBlock::TYPE_AUDIO) {
            $mediaRules = [...$mediaRules, 'mimes:mp3,wav,ogg,m4a', 'max:30720'];
        } elseif ($type === LessonContentBlock::TYPE_IMAGE) {
            $mediaRules = [...$mediaRules, 'mimes:jpg,jpeg,png,webp', 'max:8192'];
        } elseif ($type === LessonContentBlock::TYPE_PDF) {
            $mediaRules = [...$mediaRules, 'mimes:pdf', 'max:20480'];
        }

        return [
            'type' => ['required', Rule::in(LessonContentBlock::TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'video_url' => [
                Rule::requiredIf($type === LessonContentBlock::TYPE_VIDEO),
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && !$this->isYoutubeUrl((string) $value)) {
                        $fail('Вкажіть коректне посилання на YouTube.');
                    }
                },
            ],
            'media_file' => $mediaRules,
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $url = trim((string) $this->input('video_url'));

        if ($url !== '' && !str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $this->merge([
            'video_url' => $url !== '' ? $url : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    private function isYoutubeUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'youtu.be',
        ], true);
    }
}
