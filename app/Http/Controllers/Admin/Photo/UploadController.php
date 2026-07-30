<?php

namespace App\Http\Controllers\Admin\Photo;

use App\Http\Controllers\Controller;
use App\Services\Media\StandardImageService;
use Illuminate\Http\Request;
use App\Models\Photo;
use InvalidArgumentException;

class UploadController extends Controller
{
    public function __invoke(Request $request, StandardImageService $images)
    {
        $request->validate([
            'cropped_image' => ['required', 'string'],
        ]);

        try {
            $path = $images->storeDataUrl($request->string('cropped_image')->toString(), 'photos');
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('admin.photos.index')
                ->with('error', $exception->getMessage());
        }

        Photo::create(['path' => $path]);

        return redirect()->route('admin.photos.index')
            ->with('success', 'Фото завантажене у форматі WebP 1200×1200.');
    }
}
