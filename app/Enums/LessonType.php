<?php

namespace App\Enums;

enum LessonType: string
{
    case Individual = 'individual';
    case Group      = 'group';
    case Pair       = 'pair';
    case Trial      = 'trial';
}
