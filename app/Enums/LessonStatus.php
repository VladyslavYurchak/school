<?php

namespace App\Enums;

enum LessonStatus: string
{
    case Planned     = 'planned';
    case Completed   = 'completed';
    case Cancelled   = 'cancelled';
    case Rescheduled = 'rescheduled';
}
