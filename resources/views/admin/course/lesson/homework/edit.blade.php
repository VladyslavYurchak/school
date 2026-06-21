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
                        <p class="admin-subtitle">Редагування домашнього завдання, файлів та відео.</p>
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

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(count($homeworkFiles) > 0)
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <h2 class="admin-panel-title">Збережені файли</h2>
                        <span class="admin-badge admin-badge-muted">Усього: {{ count($homeworkFiles) }}</span>
                    </div>

                    <div class="admin-panel-body">
                        <ul class="admin-file-list">
                            @foreach($homeworkFiles as $file)
                                <li>
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener">
                                        <i class="bi bi-paperclip"></i>
                                        {{ basename($file) }}
                                    </a>
                                    <button type="button" class="admin-btn-danger"
                                            onclick="deleteFile('{{ route('admin.course.lesson.homework.file.delete', ['lesson' => $lesson->id, 'filename' => urlencode($file)]) }}')">
                                        <i class="bi bi-trash"></i>
                                        Видалити
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endif

            <form action="{{ route('admin.course.lesson.homework.update', $lesson->id) }}" method="POST" enctype="multipart/form-data" class="admin-panel admin-form">
                @csrf
                @method('PUT')

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Домашня частина</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="homework_text" class="admin-form-label">Текст домашнього завдання</label>
                        <textarea name="homework_text" id="homework_editor" class="form-control" rows="4">{{ old('homework_text', $lesson->homework_text) }}</textarea>
                    </div>

                    <div class="admin-form-section">
                        <label for="homework_files" class="admin-form-label">Додати файли</label>
                        <input type="file" name="homework_files[]" id="homework_files" class="form-control" multiple>
                        <div id="selected-files" class="form-text mt-2">Оберіть один або кілька файлів.</div>
                    </div>

                    <div class="admin-form-section">
                        <label for="homework_video_url" class="admin-form-label">Посилання на відео</label>
                        <input type="url" name="homework_video_url" id="homework_video_url" class="form-control" value="{{ old('homework_video_url', $lesson->homework_video_url) }}">

                        @if($lesson->homework_video_url)
                            <div class="ratio ratio-16x9 mt-3">
                                <iframe src="{{ $lesson->homework_video_url }}" frameborder="0" allowfullscreen></iframe>
                            </div>
                        @endif
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Зберегти
                        </button>
                    </div>
                </div>
            </form>

            <section class="admin-panel">
                <div class="admin-panel-body">
                    <form action="{{ route('admin.course.lesson.homework.destroy', $lesson->id) }}" method="POST"
                          onsubmit="return confirm('Видалити домашнє завдання?');" class="mb-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn-danger">
                            <i class="bi bi-trash"></i>
                            Видалити домашку
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        const input = document.getElementById('homework_files');
        const selectedFiles = document.getElementById('selected-files');

        input?.addEventListener('change', () => {
            let output = '';
            for (let i = 0; i < input.files.length; i++) {
                output += `<div>${input.files[i].name}</div>`;
            }
            selectedFiles.innerHTML = output || 'Файли не вибрані.';
        });

        function deleteFile(url) {
            if (!confirm('Видалити файл?')) return;
            const form = document.createElement('form');
            form.action = url;
            form.method = 'POST';
            form.innerHTML = `
            @csrf
            <input type="hidden" name="_method" value="DELETE">
        `;
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

            const homeworkEditor = document.querySelector('#homework_editor');
            if (homeworkEditor) {
                ClassicEditor.create(homeworkEditor, editorConfig).catch(error => console.error(error));
            }
        });
    </script>
@endsection
