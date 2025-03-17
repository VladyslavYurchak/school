<?php

namespace App\Http\Controllers\Admin\Photo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;

class UploadController extends Controller
{
    public function __invoke(Request $request)
    {
        // Перевірка, чи є обрізане зображення
        if ($request->has('cropped_image')) {
            $croppedImage = $request->input('cropped_image');

            // Перетворюємо дані зображення в файл
            $image = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $croppedImage));
            $imageName = uniqid('photo_') . '.jpg';
            $path = 'photos/' . $imageName;

            // Зберігаємо зображення
            Storage::disk('public')->put($path, $image);

            // Зберігаємо дані в базі даних
            $photo = new Photo();
            $photo->path = $path;
            $photo->save();

            return redirect()->route('admin.photos.index')->with('success', 'Фото успішно завантажене і обрізане!');
        }

        return redirect()->route('admin.photos.index')->with('error', 'Не вдалося завантажити фото.');
    }
}
