@extends('admin.layouts.layout')
@section('content')
    <main class="app-main">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light border-bottom d-flex align-items-center">
                <h3 class="mb-0 text-dark">Домашня частина уроку: {{ $lesson->title }}</h3>
                <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="btn btn-outline-dark btn-sm ms-auto">
                    ← Назад
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('admin.course.lesson.homework.store', $lesson->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method(isset($lesson->homework_text) ? 'PUT' : 'POST')

                    <!-- Текст -->
                    <div class="mb-4">
                        <label for="homework_text" class="form-label fw-semibold">Текст домашнього завдання</label>
                        <textarea name="homework_text" id="homework_text" rows="5" class="form-control border-secondary">{{ old('homework_text', $lesson->homework_text) }}</textarea>
                    </div>

                    <!-- Файли -->
                    <div class="mb-4">
                        <label for="homework_files" class="form-label fw-semibold">Додати файли</label>
                        <input type="file" name="homework_files[]" id="homework_files" class="form-control border-secondary" multiple>

                        @if($lesson->homework_files)
                            <p class="mt-3 fw-semibold">Завантажені файли:</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(json_decode($lesson->homework_files, true) as $file)
                                    <a href="{{ asset('storage/homework_files/' . $file) }}" target="_blank"
                                       class="badge bg-light text-dark border px-3 py-2">
                                        📎 {{ $file }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Відео -->
                    <div class="mb-4">
                        <label for="homework_video_url" class="form-label fw-semibold">Посилання на відео</label>
                        <input type="url" name="homework_video_url" id="homework_video_url"
                               class="form-control border-secondary"
                               value="{{ old('homework_video_url', $lesson->homework_video_url) }}">
                    </div>

                    <!-- Кнопки -->
                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="btn btn-outline-secondary me-2">Назад</a>
                        <button type="submit" class="btn btn-outline-dark">Зберегти</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <style>
        .form-control, .form-select {
            border-radius: 6px;
            box-shadow: none;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.1rem rgba(108,117,125,.25);
        }
        .btn {
            border-radius: 6px;
        }
    </style>
@endsection
