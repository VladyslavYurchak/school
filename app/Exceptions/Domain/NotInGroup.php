<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Exceptions\Domain\BaseHttpDomainException;
use Symfony\Component\HttpFoundation\Response;

final class NotInGroup extends BaseHttpDomainException
{
    protected $message = 'Lesson does not belong to the specified group.';

    protected const CODE   = 'lesson_not_in_group';
    protected const STATUS = Response::HTTP_UNPROCESSABLE_ENTITY; // 422
}
