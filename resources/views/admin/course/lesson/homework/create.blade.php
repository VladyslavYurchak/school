@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-house-check"></i>
                            Домашнє завдання
                        </span>
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                        <p class="admin-subtitle">Текст домашнього завдання, файли та відео.</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.edit', $lesson->id) }}" class="admin-btn-soft">
                            <i class="bi bi-pencil"></i>
                            Редагувати урок
                        </a>
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До курсу
                        </a>
                    </div>
                </div>
            </section>

            <form action="{{ route('admin.course.lesson.homework.store', $lesson->id) }}" method="POST" enctype="multipart/form-data" class="admin-panel admin-form">
                @csrf

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Домашня частина</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="homework_text" class="admin-form-label">Текст домашнього завдання</label>
                        <textarea name="homework_text" id="homework_editor" class="form-control">{{ old('homework_text') }}</textarea>
                    </div>

                    <div class="admin-form-section">
                        <label for="homework_files" class="admin-form-label">Додати файли</label>
                        <input type="file" name="homework_files[]" id="homework_files" class="form-control" multiple>

                        @if(!empty($lesson->homework_files))
                            <div class="admin-content-box mt-3">
                                <strong>Завантажені файли:</strong>
                                <ul class="admin-file-list mt-2">
                                    @foreach(($lesson->homework_files ?? []) as $file)
                                        <li>
                                            <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener">
                                                <i class="bi bi-paperclip"></i>
                                                {{ basename($file) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="admin-form-section">
                        <label for="homework_video_url" class="admin-form-label">Посилання на відео</label>
                        <input type="url" name="homework_video_url" id="homework_video_url" class="form-control" value="{{ old('homework_video_url', $lesson->homework_video_url) }}">
                    </div>

                    <div class="admin-form-actions">
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="admin-btn-soft">
                            <i class="bi bi-x-lg"></i>
                            Скасувати
                        </a>
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Зберегти
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorConfig = {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo']
            };

            const homeworkEditor = document.querySelector('#homework_editor');
            if (homeworkEditor) {
                ClassicEditor.create(homeworkEditor, editorConfig).catch(error => console.error(error));
            }
        });
    </script>
@endsection
