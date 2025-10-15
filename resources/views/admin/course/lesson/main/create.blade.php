@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-white d-flex align-items-center">
                <h3 class="fw-bold text-dark mb-0">{{ $lesson->title }}</h3>
                <a href="{{ route('admin.course.index') }}" class="btn btn-outline-secondary btn-sm ms-auto">
                    ← Назад
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('admin.course.lesson.main.update', $lesson->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Основний текст --}}
                    <div class="mb-4">
                        <label for="content" class="form-label fw-bold">Основний зміст</label>
                        <textarea name="content" id="content" rows="5" class="form-control shadow-sm">{{ old('content', $lesson->content) }}</textarea>
                    </div>

                    {{-- Посилання на відео --}}
                    <div class="mb-4">
                        <label for="video_url" class="form-label fw-bold">Посилання на відео</label>
                        <input type="url" name="video_url" id="video_url" class="form-control shadow-sm"
                               value="{{ old('video_url', $lesson->video_url) }}">
                    </div>

                    {{-- Завантаження файлів --}}
                    <div class="mb-4">
                        <label for="media" class="form-label fw-bold">Матеріали</label>
                        <input type="file" name="media[]" id="media" class="form-control shadow-sm" multiple>

                        @if($lesson->media && count($lesson->media) > 0)
                            <ul class="list-group list-group-flush mt-3 shadow-sm rounded">
                                @foreach($lesson->media as $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        📎 {{ $file->name }}
                                        <a href="{{ route('admin.course.lesson.main.deleteMedia', $file->id) }}"
                                           onclick="return confirm('Видалити цей файл?')"
                                           class="btn btn-sm btn-outline-danger">
                                            🗑️
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Аудіо --}}
                    <div class="mb-4">
                        <label for="audio" class="form-label fw-bold">Аудіо</label>
                        <input type="file" name="audio" id="audio" class="form-control shadow-sm">

                        @if($lesson->audio)
                            <div class="mt-3 p-2 bg-light rounded shadow-sm d-flex justify-content-between align-items-center">
                                🎵 {{ basename($lesson->audio) }}
                                <a href="{{ route('admin.course.lesson.main.deleteAudio', $lesson->id) }}"
                                   onclick="return confirm('Видалити аудіо?')"
                                   class="btn btn-sm btn-outline-danger">
                                    🗑️
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Кнопки дій --}}
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <form action="{{ route('admin.course.lesson.main.destroy', $lesson->id) }}"
                              method="POST"
                              onsubmit="return confirm('Ви впевнені, що хочете видалити основну частину уроку?');"
                              class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger shadow-sm">
                                🗑️ Видалити
                            </button>
                        </form>

                        <button type="submit" class="btn btn-success shadow-sm">
                            💾 Оновити основну частину
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const inputMedia = document.getElementById('media_files');
        const selectedMedia = document.getElementById('selected-media-files');

        inputMedia?.addEventListener('change', () => {
            let output = '';
            for (let i = 0; i < inputMedia.files.length; i++) {
                output += `<div>📎 ${inputMedia.files[i].name}</div>`;
            }
            selectedMedia.innerHTML = output || 'Файли не вибрані.';
        });

        function deleteFile(url) {
            if (!confirm('Видалити файл?')) return;
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
@endsection
