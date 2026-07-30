<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

class StandardImageService
{
    public const WIDTH = 1200;

    public const HEIGHT = 1200;

    private const MAX_DECODED_BYTES = 12 * 1024 * 1024;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function storeUploaded(UploadedFile $file, string $directory): string
    {
        return $this->storeImageData(
            file_get_contents($file->getRealPath()),
            $directory
        );
    }

    public function storeDataUrl(string $dataUrl, string $directory): string
    {
        if (! preg_match('/^data:image\/(?:jpeg|png|webp);base64,/', $dataUrl)) {
            throw new InvalidArgumentException('Невірний формат зображення.');
        }

        $imageData = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);

        if ($imageData === false || strlen($imageData) > self::MAX_DECODED_BYTES) {
            throw new InvalidArgumentException('Не вдалося обробити зображення або файл завеликий.');
        }

        return $this->storeImageData($imageData, $directory);
    }

    private function storeImageData(string $imageData, string $directory): string
    {
        $image = $this->manager
            ->read($imageData)
            ->cover(self::WIDTH, self::HEIGHT);

        $path = trim($directory, '/') . '/' . Str::uuid() . '.webp';

        Storage::disk('public')->put($path, (string) $image->toWebp(86));

        return $path;
    }
}
