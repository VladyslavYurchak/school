@extends('admin.layouts.layout')

@section('content')
    <!-- Cropper.js CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">

    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

    <div class="container mt-4">
        <h3 class="fw-bold">Завантажити фото</h3>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Контейнер для обрізки -->
        <div id="crop-container" style="display: none; width: 350px; margin: 0 auto;">
            <img id="image-to-crop" src="" alt="Фото для обрізки" style="width: 100%; height: auto;">
        </div>

        <!-- Форма завантаження -->
        <form action="{{ route('admin.photos.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <input type="file" name="photo" id="photo" class="form-control" required>
            </div>

            <!-- Кнопка для відправки форми -->
            <button type="submit" id="submit-button" class="btn btn-success" disabled>Завантажити</button>
        </form>

        <h3 class="fw-bold mt-4">Галерея</h3>
        <div class="row">
            @foreach ($photos as $photo)
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <img src="{{ asset('storage/' . $photo->path) }}" class="card-img-top" alt="Фото">
                        <div class="card-body text-center">
                            <form action="{{ route('admin.photos.delete', $photo->id) }}" method="POST" onsubmit="return confirm('Ви впевнені, що хочете видалити це фото?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Видалити</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Стилі для контейнера обрізки -->
    <style>
        #crop-container {
            width: 350px; /* Зменшуємо контейнер для обрізки */
            margin: 0 auto;
        }
        #image-to-crop {
            width: 100%; /* Налаштовуємо, щоб зображення автоматично підлаштовувалося під контейнер */
            height: auto;
        }
    </style>

    <!-- JavaScript для обрізки -->
    <script>
        const photoInput = document.getElementById('photo');
        const cropContainer = document.getElementById('crop-container');
        const submitButton = document.getElementById('submit-button');
        const imageElement = document.getElementById('image-to-crop');
        let cropper; // Змінна для об'єкта Cropper.js

        // Крок 1: Вибір фото
        photoInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imageElement.src = e.target.result;
                    cropContainer.style.display = 'block';  // Показуємо контейнер для обрізки
                    submitButton.disabled = false;         // Дозволяємо відправити форму
                    if (cropper) {
                        cropper.destroy();  // Якщо попередній Cropper існує, знищуємо його
                    }
                    cropper = new Cropper(imageElement, {
                        aspectRatio: 1, // Пропорції 1:1 для квадратного обрізання
                        viewMode: 1,
                        scalable: true,
                        zoomable: true,
                        autoCropArea: 1, // Обрізаємо все зображення
                        responsive: true,
                        ready: function () {
                            // Фіксовані розміри для обрізки
                            cropper.setCropBoxData({
                                left: 0,
                                top: 0,
                                width: 300, // Ширина обрізаного зображення
                                height: 300, // Висота обрізаного зображення
                            });
                        },
                    });
                };
                reader.readAsDataURL(file); // Завантажуємо фото в елемент img
            }
        });

        // Крок 2: Обрізати і передати обрізане фото
        submitButton.addEventListener('click', function (event) {
            event.preventDefault(); // Запобігаємо стандартній поведінці форми

            const canvas = cropper.getCroppedCanvas({
                width: 300,  // Задаємо ширину для обрізаного фото
                height: 300, // Задаємо висоту для обрізаного фото
            });
            const fileType = photoInput.files[0].type; // Отримуємо формат файлу
            const imageFormat = fileType === 'image/png' ? 'image/png' : 'image/jpeg';
            const croppedImage = canvas.toDataURL(imageFormat, 1.0); // Використовуємо правильний формат

            // Створюємо прихований інпут для обрізаного фото
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'cropped_image';
            hiddenInput.value = croppedImage;
            document.querySelector('form').appendChild(hiddenInput);

            // Відправляємо форму
            document.querySelector('form').submit();
        });
    </script>
@endsection
