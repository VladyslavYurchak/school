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

                    <!-- Текст -->
                    <div class="mb-4">
                        <label for="homework_text" class="form-label fw-semibold">Текст домашнього завдання</label>
                        <textarea
                            name="homework_text"
                            id="homework_editor"
                            class="form-control"
                        >{{ old('homework_text') }}</textarea>
                    </div>

                    <!-- Файли -->
                    <div class="mb-4">
                        <label for="homework_files" class="form-label fw-semibold">Додати файли</label>
                        <input type="file" name="homework_files[]" id="homework_files" class="form-control border-secondary" multiple>

                        @if(!empty($lesson->homework_files))
                            <p class="mt-3 fw-semibold">Завантажені файли:</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(($lesson->homework_files ?? []) as $file)
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank"
                                       class="badge bg-light text-dark border px-3 py-2">
                                        📎 {{ basename($file) }}
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

    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorConfig = {
                toolbar: [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'link',
                    'bulletedList',
                    'numberedList',
                    'blockQuote',
                    '|',
                    'undo',
                    'redo'
                ]
            };

            if (document.querySelector('#content_editor')) {
                ClassicEditor
                    .create(document.querySelector('#content_editor'), editorConfig)
                    .catch(error => {
                        console.error(error);
                    });
            }

            if (document.querySelector('#homework_editor')) {
                ClassicEditor
                    .create(document.querySelector('#homework_editor'), editorConfig)
                    .catch(error => {
                        console.error(error);
                    });
            }
        });
    </script>
@endsection
