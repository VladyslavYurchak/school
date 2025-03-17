<?php

namespace App\Http\Controllers\Admin\Photo;

use App\Models\Photo; // Додайте імпорт моделі Photo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeleteController
{
    public function __invoke(Photo $photo)
    {
        // Видалення файлу з диска
        if (Storage::disk('public')->exists($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }

        // Видалення запису з бази даних
        $photo->delete();

        // Повертаємось назад на сторінку з повідомленням про успіх
        return redirect()->route('admin.photos.index')->with('success', 'Фото успішно видалене!');
    }
}
