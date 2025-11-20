<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\Calendar;

use App\Exceptions\Domain\BaseHttpDomainException;

final class GroupMismatch extends BaseHttpDomainException
{
    protected $message = 'Lesson does not belong to this group.';
    protected const CODE   = 'group_mismatch';
    protected const STATUS = 422;
}
