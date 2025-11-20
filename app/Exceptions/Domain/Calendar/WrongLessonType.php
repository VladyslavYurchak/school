<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\Calendar;

use App\Exceptions\Domain\BaseHttpDomainException;

final class WrongLessonType extends BaseHttpDomainException
{
    protected $message = 'This endpoint is only for group or pair lessons.';
    protected const CODE   = 'wrong_lesson_type';
    protected const STATUS = 422;
}
