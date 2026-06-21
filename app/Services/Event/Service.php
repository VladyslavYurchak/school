<?php

namespace App\Services\Event;

use App\Models\Event;
use Illuminate\Http\UploadedFile;

class Service
{
    public function store($data)
    {
        $data = $this->prepareImage($data, 'events');

        Event::create($data);
    }

    public function update($event, $data)
    {
        $data = $this->prepareImage($data, 'events');

        $event->update($data);
    }

    private function prepareImage(array $data, string $directory): array
    {
        if (($data['image_file'] ?? null) instanceof UploadedFile) {
            $data['image'] = $data['image_file']->store($directory, 'public');
        }

        unset($data['image_file']);

        return $data;
    }
}
