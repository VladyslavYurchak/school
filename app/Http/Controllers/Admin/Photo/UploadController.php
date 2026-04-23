<?php

namespace App\Http\Controllers\Admin\Photo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class UploadController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->filled('cropped_image')) {
            return redirect()->route('admin.photos.index')
                ->with('error', 'Немає зображення.');
        }

        $base64 = $request->input('cropped_image');

        // Витягуємо дані
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64)) {
            return redirect()->route('admin.photos.index')
                ->with('error', 'Невірний формат.');
        }

        $imageData = substr($base64, strpos($base64, ',') + 1);
        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            return redirect()->route('admin.photos.index')
                ->with('error', 'Помилка декодування.');
        }

        // Ініціалізація Intervention
        $manager = new ImageManager(new Driver());

        $image = $manager->read($imageData);

        /**
         * 🔥 ВАЖЛИВО:
         * тут контролюється якість і розмір
         */

        // Якщо велике — зменшуємо (щоб не було 5MB фото)
        if ($image->width() > 2000) {
            $image->scale(width: 2000);
        }

        // Конвертація в WebP з хорошою якістю
        $encoded = $image->toWebp(90); // 90 = дуже гарний баланс

        // Генеруємо ім’я
        $imageName = uniqid('photo_') . '.webp';
        $path = 'photos/' . $imageName;

        // Зберігаємо
        Storage::disk('public')->put($path, (string) $encoded);

        // DB
        $photo = new Photo();
        $photo->path = $path;
        $photo->save();

        return redirect()->route('admin.photos.index')
            ->with('success', 'Фото завантажене (WebP, оптимізоване)');
    }
}
