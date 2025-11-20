<?php

// app/Http/Requests/Admin/Calendar/EventIndexRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\CarbonImmutable;

final class EventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // або Policy на teacher
    }

    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date'],
            'end'   => ['nullable', 'date'],
        ];
    }

    public function range(): array
    {
        $tz = $this->user()?->timezone ?? config('app.timezone', 'UTC');

        $start = $this->input('start')
            ? CarbonImmutable::parse($this->input('start'), $tz)
            : now($tz)->subDays(60)->toImmutable();

        $end = $this->input('end')
            ? CarbonImmutable::parse($this->input('end'), $tz)
            : now($tz)->addDays(60)->toImmutable();

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->addDay();
        }

        // повертай в єдиній TZ, напр., у UTC
        return [$start->utc(), $end->utc()];
    }
}
