@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="form-title"><i class="fas fa-book-reader"></i> Перегляд уроку: {{ $lesson->title }}</h4>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h5><i class="fas fa-chevron-circle-right text-primary"></i> Вид уроку:</h5>
                    <p class="p-2 border rounded bg-light">
                        @if ($lesson->lesson_type === 'Reading')
                            📖 Читання
                        @elseif ($lesson->lesson_type === 'Listening')
                            🎧 Аудіювання
                        @elseif ($lesson->lesson_type === 'Grammar')
                            📝 Граматика
                        @elseif ($lesson->lesson_type === 'Speaking')
                            🗣️ Розмовна частина
                        @elseif ($lesson->lesson_type === 'Test')
                            ✅ Тест
                        @else
                            Невідомий вид
                        @endif
                    </p>
                </div>

                <div class="mb-4">
                    <h5><i class="fas fa-align-left text-primary"></i> Основний зміст уроку:</h5>
                    <p class="p-2 border rounded bg-light">{{ $lesson->content ?? 'Зміст відсутній' }}</p>
                </div>

                @if ($lesson->video_url)
                    <div class="mb-4">
                        <h5><i class="fas fa-video text-primary"></i> Відеоурок:</h5>
                        <a href="{{ $lesson->video_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            📹 Переглянути відео
                        </a>
                    </div>
                @endif

                <!-- Прикріплені файли -->
                @php
                    $mediaFiles = json_decode($lesson->media_files, true) ?? [];
                @endphp
                @if (!empty($mediaFiles))
                    <div class="mb-4">
                        <h5><i class="fas fa-paperclip text-primary"></i> Прикріплені файли до уроку:</h5>
                        <ul class="list-group">
                            @foreach ($mediaFiles as $file)
                                <li class="list-group-item">
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank" class="text-primary">
                                        📄 {{ basename($file) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Домашнє завдання -->
                @php
                    $homeworkFiles = json_decode($lesson->homework_files, true) ?? [];
                @endphp
                @if (!empty($lesson->homework_text) || !empty($homeworkFiles) || !empty($lesson->homework_video_url))
                    <div class="mb-4">
                        <h5><i class="fas fa-house-user text-primary"></i> Домашнє завдання:</h5>
                        @if (!empty($lesson->homework_text))
                            <p class="p-2 border rounded bg-light">{{ $lesson->homework_text }}</p>
                        @endif

                        @if (!empty($homeworkFiles))
                            <h6><i class="fas fa-file-alt text-primary"></i> Прикріплені файли:</h6>
                            <ul class="list-group mb-2">
                                @foreach ($homeworkFiles as $file)
                                    <li class="list-group-item">
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="text-primary">
                                            📂 {{ basename($file) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (!empty($lesson->homework_video_url))
                            <p><i class="fas fa-video"></i> Відео для домашнього завдання:
                                <a href="{{ $lesson->homework_video_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    Переглянути відео
                                </a>
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="card-footer">
                <a href="{{ route('admin.course.show', ['course' => $lesson->course_id]) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-circle-left"></i> Назад до курсу
                </a>
            </div>
        </div>
    </div>
@endsection
