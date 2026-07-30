<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Services\Media\StandardImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Service
{
    public function __construct(
        private readonly StandardImageService $images
    ) {
    }

    public function store($data)
    {
        $data = $this->prepareImage($data, 'events');

        Event::create($data);
    }

    public function update($event, $data)
    {
        $oldImage = $event->image;
        $data = $this->prepareImage($data, 'events');

        $event->update($data);

        if (
            $oldImage
            && $oldImage !== $event->image
            && ! str_starts_with($oldImage, 'http://')
            && ! str_starts_with($oldImage, 'https://')
        ) {
            Storage::disk('public')->delete($oldImage);
        }
    }

    private function prepareImage(array $data, string $directory): array
    {
        if (! empty($data['cropped_image'])) {
            $data['image'] = $this->images->storeDataUrl($data['cropped_image'], $directory);
        } elseif (($data['image_file'] ?? null) instanceof UploadedFile) {
            $data['image'] = $this->images->storeUploaded($data['image_file'], $directory);
        }

        unset($data['cropped_image'], $data['image_file']);

        $data['image'] = $data['image'] ?? '';

        return $data;
    }
}
