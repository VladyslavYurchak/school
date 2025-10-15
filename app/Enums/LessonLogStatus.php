<?php

namespace App\Enums;

enum LessonLogStatus: string
{
    case Completed   = 'completed';
    case Charged     = 'charged';
    case Rescheduled = 'rescheduled';
}
