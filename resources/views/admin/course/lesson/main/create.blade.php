@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-journal-richtext"></i>
                            Основне завдання
                        </span>
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                        <p class="admin-subtitle">Основний зміст уроку, відео, аудіо та додаткові матеріали.</p>
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

            <form action="{{ route('admin.course.lesson.main.update', $lesson->id) }}" method="POST" enctype="multipart/form-data" class="admin-panel admin-form">
                @csrf
                @method('PUT')

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Основна частина</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="content_editor" class="admin-form-label">Основний зміст</label>
                        <textarea name="content" id="content_editor" class="form-control">{{ old('content', $lesson->content) }}</textarea>
                    </div>

                    <div class="admin-form-section">
                        <label for="video_url" class="admin-form-label">Посилання на відео</label>
                        <input type="url" name="video_url" id="video_url" class="form-control" value="{{ old('video_url', $lesson->video_url) }}">
                    </div>

                    <div class="admin-form-section">
                        <label for="media_files" class="admin-form-label">Матеріали</label>
                        <input type="file" name="media_files[]" id="media_files" class="form-control" multiple>
                        <div id="selected-media-files" class="form-text mt-2">Файли не вибрані.</div>

                        @if(count($mediaFiles) > 0)
                            <div class="admin-content-box mt-3">
                                <strong>Завантажені матеріали:</strong>
                                <ul class="admin-file-list mt-2">
                                    @foreach($mediaFiles as $file)
                                        <li>
                                            <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener">
                                                <i class="bi bi-paperclip"></i>
                                                {{ basename($file) }}
                                            </a>
                                            <button type="button" class="admin-btn-danger"
                                                    onclick="deleteFile('{{ route('admin.course.lesson.main.file.delete', ['lesson' => $lesson->id, 'filename' => urlencode($file)]) }}')">
                                                <i class="bi bi-trash"></i>
                                                Видалити
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="admin-form-section">
                        <label for="audio_file" class="admin-form-label">Аудіо</label>
                        <input type="file" name="audio_file" id="audio_file" class="form-control">

                        @if($lesson->audio_file)
                            <div class="admin-content-box mt-3">
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                    <span>{{ basename($lesson->audio_file) }}</span>
                                    <button type="button" class="admin-btn-danger"
                                            onclick="deleteFile('{{ route('admin.course.lesson.main.audio.delete', $lesson->id) }}', 'Видалити аудіо?')">
                                        <i class="bi bi-trash"></i>
                                        Видалити
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="admin-form-actions">
                        <button type="button" class="admin-btn-danger"
                                onclick="deleteFile('{{ route('admin.course.lesson.main.destroy', $lesson->id) }}', 'Видалити основну частину уроку?')">
                            <i class="bi bi-trash"></i>
                            Видалити
                        </button>

                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Зберегти
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const inputMedia = document.getElementById('media_files');
        const selectedMedia = document.getElementById('selected-media-files');

        inputMedia?.addEventListener('change', () => {
            let output = '';

            for (let i = 0; i < inputMedia.files.length; i++) {
                output += `<div>${inputMedia.files[i].name}</div>`;
            }

            selectedMedia.innerHTML = output || 'Файли не вибрані.';
        });

        function deleteFile(url, message = 'Видалити файл?') {
            if (!confirm(message)) return;

            const form = document.createElement('form');
            form.action = url;
            form.method = 'POST';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';

            form.appendChild(csrf);
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorConfig = {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo']
            };

            const contentEditor = document.querySelector('#content_editor');
            if (contentEditor) {
                ClassicEditor.create(contentEditor, editorConfig).catch(error => console.error(error));
            }
        });
    </script>
@endsection
