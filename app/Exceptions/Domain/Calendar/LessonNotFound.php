<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\Calendar;

use App\Exceptions\Domain\BaseHttpDomainException;

final class LessonNotFound extends BaseHttpDomainException
{
    protected $message = 'Lesson not found.';
    protected const CODE   = 'lesson_not_found';
    protected const STATUS = 404;
}
