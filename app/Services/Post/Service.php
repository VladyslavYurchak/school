<?php

namespace App\Services\Post;

use App\Models\Post;
use Illuminate\Http\UploadedFile;

class Service
{
    public function store($data)
    {
        $data = $this->prepareImage($data, 'posts');

        Post::create($data);
    }

    public function update($post, $data)
    {
        $data = $this->prepareImage($data, 'posts');

        $post->update($data);
    }

    private function prepareImage(array $data, string $directory): array
    {
        if (($data['image_file'] ?? null) instanceof UploadedFile) {
            $data['image'] = $data['image_file']->store($directory, 'public');
        }

        unset($data['image_file']);

        $data['image'] = $data['image'] ?? '';

        return $data;
    }
}
