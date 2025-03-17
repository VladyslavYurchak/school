<?php

namespace App\Services\Event;

use App\Models\Event;

class Service
{
    public function store($data)
        {
            Event::create($data);
        }
    public function update($event, $data)
    {
        $event->update($data);
    }
}
