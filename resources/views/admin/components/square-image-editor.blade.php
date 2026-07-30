@php
    $editorId = $editorId ?? 'square-image';
    $currentImageUrl = $currentImageUrl ?? '';
    $currentImageValue = $currentImageValue ?? '';
@endphp

@once
    @push('styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    @endpush
@endonce

<div data-square-image-editor>
    <div class="admin-form-section">
        <label for="{{ $editorId }}-url" class="admin-form-label">Фото за URL</label>

        @if($currentImageUrl)
            <div class="admin-square-image-preview mb-3">
                <img src="{{ $currentImageUrl }}" alt="Поточне фото">
            </div>
        @endif

        <input type="text"
               name="image"
               id="{{ $editorId }}-url"
               class="form-control @error('image') is-invalid @enderror"
               value="{{ old('image', $currentImageValue) }}"
               maxlength="255"
               placeholder="https://example.com/photo.jpg">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Можна залишити поточне фото, вставити URL або завантажити файл нижче.</div>
    </div>

    <div class="admin-form-section">
        <label for="{{ $editorId }}-file" class="admin-form-label">Завантажити та обрізати фото</label>
        <input type="file"
               name="image_file"
               id="{{ $editorId }}-file"
               class="form-control @error('image_file') is-invalid @enderror"
               accept="image/jpeg,image/png,image/webp"
               data-square-image-input>
        @error('image_file')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @error('cropped_image')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror
        <div class="form-text">Після вибору пересувай і масштабуй фото в квадратній рамці. Результат: WebP 1200×1200.</div>
    </div>

    <div class="admin-crop-container d-none" data-square-crop-container>
        <img src="" alt="Фото для обрізання" data-square-crop-image>
    </div>

    <input type="hidden" name="cropped_image" value="" data-square-cropped-value>
</div>

@once
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-square-image-editor]').forEach(function (editor) {
                    const form = editor.closest('form');
                    const fileInput = editor.querySelector('[data-square-image-input]');
                    const cropContainer = editor.querySelector('[data-square-crop-container]');
                    const image = editor.querySelector('[data-square-crop-image]');
                    const croppedValue = editor.querySelector('[data-square-cropped-value]');
                    let cropper = null;

                    fileInput?.addEventListener('change', function () {
                        const file = fileInput.files?.[0];

                        if (!file) {
                            cropper?.destroy();
                            cropper = null;
                            cropContainer.classList.add('d-none');
                            croppedValue.value = '';
                            return;
                        }

                        const reader = new FileReader();

                        reader.onload = function (event) {
                            cropper?.destroy();
                            image.src = event.target.result;
                            cropContainer.classList.remove('d-none');
                            cropper = new Cropper(image, {
                                aspectRatio: 1,
                                viewMode: 1,
                                autoCropArea: 1,
                                responsive: true,
                                movable: true,
                                zoomable: true,
                                scalable: false,
                                rotatable: false,
                            });
                        };

                        reader.readAsDataURL(file);
                    });

                    form?.addEventListener('submit', function (event) {
                        if (!cropper || editor.dataset.cropReady === 'true') {
                            return;
                        }

                        event.preventDefault();

                        const canvas = cropper.getCroppedCanvas({
                            width: 1200,
                            height: 1200,
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });

                        croppedValue.value = canvas.toDataURL('image/webp', 0.9);
                        fileInput.disabled = true;
                        editor.dataset.cropReady = 'true';
                        form.submit();
                    });
                });
            });
        </script>
    @endpush
@endonce
