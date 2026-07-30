@extends('admin.layouts.layout')

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-images"></i>
                            Сайт
                        </span>
                        <h1 class="admin-title">Фото</h1>
                        <p class="admin-subtitle">
                            Завантажуйте фото для галереї на головній сторінці. Перед збереженням фото можна обрізати.
                        </p>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Завантажити фото</h2>
                </div>

                <div class="admin-panel-body">
                    <form id="photo-upload-form"
                          action="{{ route('admin.photos.upload') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="admin-form">
                        @csrf

                        <div class="admin-form-section">
                            <label for="photo" class="admin-form-label">Оберіть фото</label>
                            <input type="file" name="photo" id="photo" class="form-control" accept="image/*" required>
                            <div class="form-text">Після вибору фото зʼявиться область обрізки.</div>
                        </div>

                        <div id="crop-container" class="admin-crop-container d-none">
                            <img id="image-to-crop" src="" alt="Фото для обрізки">
                        </div>

                        <div class="admin-form-actions mt-3">
                            <button type="submit" id="submit-button" class="admin-btn-primary" disabled>
                                <i class="bi bi-upload"></i>
                                Завантажити
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Галерея</h2>
                    <span class="admin-badge admin-badge-muted">
                        Усього: {{ $photos->count() }}
                    </span>
                </div>

                <div class="admin-panel-body">
                    @if($photos->count())
                        <div class="admin-photo-grid">
                            @foreach($photos as $photo)
                                <article class="admin-photo-card">
                                    <img src="{{ asset('storage/' . $photo->path) }}" alt="Фото">

                                    <form action="{{ route('admin.photos.delete', $photo) }}"
                                          method="POST"
                                          onsubmit="return confirm('Видалити це фото?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-trash"></i>
                                            Видалити
                                        </button>
                                    </form>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-images"></i>
                            </div>
                            <h2 class="h5">Фото поки немає</h2>
                            <p class="mb-0">Завантажте перше фото, щоб галерея на головній стала живішою.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('photo-upload-form');
            const photoInput = document.getElementById('photo');
            const cropContainer = document.getElementById('crop-container');
            const submitButton = document.getElementById('submit-button');
            const imageElement = document.getElementById('image-to-crop');
            let cropper;

            photoInput?.addEventListener('change', function (event) {
                const file = event.target.files[0];

                if (!file) {
                    cropContainer?.classList.add('d-none');
                    submitButton.disabled = true;
                    return;
                }

                const reader = new FileReader();

                reader.onload = function (e) {
                    imageElement.src = e.target.result;
                    cropContainer.classList.remove('d-none');
                    submitButton.disabled = false;

                    if (cropper) {
                        cropper.destroy();
                    }

                    cropper = new Cropper(imageElement, {
                        aspectRatio: 1,
                        viewMode: 1,
                        scalable: true,
                        zoomable: true,
                        autoCropArea: 1,
                        responsive: true,
                    });
                };

                reader.readAsDataURL(file);
            });

            form?.addEventListener('submit', function (event) {
                if (!cropper) {
                    return;
                }

                event.preventDefault();

                const canvas = cropper.getCroppedCanvas({
                    width: 1200,
                    height: 1200,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'cropped_image';
                hiddenInput.value = canvas.toDataURL('image/webp', 0.95);
                form.appendChild(hiddenInput);

                form.submit();
            });
        });
    </script>
@endpush
