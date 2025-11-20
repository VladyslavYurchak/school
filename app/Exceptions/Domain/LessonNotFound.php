<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;

final class LessonNotFound extends DomainException
{
    public function code(): string  { return 'lesson_not_found'; }
    public function status(): int   { return Response::HTTP_NOT_FOUND; } // 404
}
