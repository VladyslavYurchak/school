@extends('admin.layouts.layout')

@section('content')
    <div class="container my-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light border-bottom d-flex align-items-center">
                <h3 class="fw-bold mb-0 text-dark">Редагування домашнього завдання – {{ $lesson->title }}</h3>
                <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="btn btn-outline-secondary btn-sm ms-auto">
                    ← Назад до курсу
                </a>
            </div>

            <div class="card-body">
                {{-- Flash --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        ✅ {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
                    </div>
                @endif

                {{-- Збережені файли --}}
                @if(count($homeworkFiles) > 0)
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-dark">📁 Збережені файли:</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($homeworkFiles as $file)
                                <div class="d-flex align-items-center bg-light border rounded px-3 py-2 shadow-sm">
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank" class="text-decoration-none text-primary">
                                        📎 {{ basename($file) }}
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2"
                                            onclick="deleteFile('{{ route('admin.course.lesson.homework.file.delete', ['lesson' => $lesson->id, 'filename' => urlencode(basename($file))]) }}')">
                                        🗑️
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Форма --}}
                <form action="{{ route('admin.course.lesson.homework.update', $lesson->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="homework_text" class="form-label fw-semibold text-dark">📝 Текст домашнього завдання</label>
                        <textarea name="homework_text" id="homework_text" class="form-control rounded shadow-sm" rows="4">{{ old('homework_text', $lesson->homework_text) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="homework_files" class="form-label fw-semibold text-dark">📎 Додати файли</label>
                        <input type="file" name="homework_files[]" id="homework_files" class="form-control shadow-sm" multiple>
                        <small id="selected-files" class="form-text text-muted mt-1">Оберіть один або кілька файлів</small>
                    </div>

                    <div class="mb-4">
                        <label for="homework_video_url" class="form-label fw-semibold text-dark">🎥 Посилання на відео</label>
                        <input type="url" name="homework_video_url" id="homework_video_url" class="form-control shadow-sm"
                               value="{{ old('homework_video_url', $lesson->homework_video_url) }}">

                        {{-- Попередній перегляд відео --}}
                        @if($lesson->homework_video_url)
                            <div class="mt-3 ratio ratio-16x9 shadow-sm rounded">
                                <iframe src="{{ $lesson->homework_video_url }}" frameborder="0" allowfullscreen></iframe>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-dark">
                            💾 Оновити домашнє завдання
                        </button>
                    </div>
                </form>
            </div>

            {{-- Низ --}}
            <div class="card-footer d-flex justify-content-between">
                <form action="{{ route('admin.course.lesson.homework.destroy', $lesson->id) }}" method="POST"
                      onsubmit="return confirm('Ви впевнені, що хочете видалити домашнє завдання?');" class="mb-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">🗑️ Видалити домашку</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Скрипти --}}
    <script>
        const input = document.getElementById('homework_files');
        const selectedFiles = document.getElementById('selected-files');

        input?.addEventListener('change', () => {
            let output = '';
            for (let i = 0; i < input.files.length; i++) {
                output += `<div>📎 ${input.files[i].name}</div>`;
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
@endsection
