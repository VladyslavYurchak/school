<?php

require __DIR__.'/public.php';
require __DIR__.'/admin.php';
require __DIR__.'/teacher.php';
require __DIR__.'/student.php';
require __DIR__.'/testing.php';
require __DIR__.'/webhooks.php';

if (app()->environment('local')) {
    require_once __DIR__.'/dev.php';
}
