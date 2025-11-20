<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class MarkGroupAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|exists:groups,id',
            'lesson_id' => 'required|exists:planned_lessons,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'present_students' => 'array',
            'present_students.*' => 'exists:students,id',
        ];
    }
}
