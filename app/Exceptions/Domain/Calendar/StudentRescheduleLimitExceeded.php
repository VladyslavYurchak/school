<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\Calendar;

use App\Exceptions\Domain\BaseHttpDomainException;

final class StudentRescheduleLimitExceeded extends BaseHttpDomainException
{
    protected $message = 'Student has exceeded the monthly reschedule limit.';
    protected const CODE   = 'student_reschedule_limit';
    protected const STATUS = 403;
}
