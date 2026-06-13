<?php

namespace App\Http\Requests\Admin\Calendar;

use App\Services\Calendar\CalendarAccessService;
use App\Services\Calendar\CalendarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'time' => ['required'],
            'duration' => ['nullable', 'integer', 'min:15', 'max:180'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Date is required.',
            'date.date' => 'Date format is invalid.',
            'time.required' => 'Time is required.',
            'duration.integer' => 'Duration must be a number of minutes.',
            'duration.min' => 'Minimum duration is 15 minutes.',
            'duration.max' => 'Maximum duration is 180 minutes.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'duration' => $this->duration !== null ? (int) $this->duration : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $teacherId = optional($this->user()?->teacher)->id;

            if (!$teacherId) {
                return;
            }

            try {
                $start = Carbon::parse($this->input('date').' '.$this->input('time'));
            } catch (\Throwable) {
                return;
            }

            $duration = (int) ($this->input('duration') ?? 60);
            $duration = max(15, min($duration, 180));
            $end = $start->copy()->addMinutes($duration);

            $lessonId = (int) $this->route('id');
            $access = app(CalendarAccessService::class);

            if (!$access->lessonBelongsToTeacher($lessonId, $teacherId)) {
                return;
            }

            $hasOverlap = app(CalendarAvailabilityService::class)
                ->teacherHasOverlap($teacherId, $start, $end, $lessonId);

            if ($hasOverlap) {
                $v->errors()->add('date', 'Teacher already has another lesson at this time.');
            }
        });
    }
}
