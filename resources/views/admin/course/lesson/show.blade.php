@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <!-- Основна частина уроку -->
            <div class="col-md-7">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-book-reader"></i> Урок: {{ $lesson->title }}</h4>
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
                                    ❓ Невідомий вид
                                @endif
                            </p>
                        </div>

                        <div class="mb-4">
                            <h5><i class="fas fa-align-left text-primary"></i> Основний зміст:</h5>
                            <div class="p-3 border rounded bg-light">{{ $lesson->content ?? 'Зміст відсутній' }}</div>
                        </div>

                        @if ($lesson->video_url)
                            <div class="mb-4 text-center">
                                <a href="{{ $lesson->video_url }}" target="_blank" class="btn btn-primary btn-lg">
                                    <i class="fas fa-video"></i> Переглянути відео
                                </a>
                            </div>
                        @endif

                        @php
                            $mediaFiles = json_decode($lesson->media_files, true) ?? [];
                        @endphp
                        @if (!empty($mediaFiles))
                            <div class="mb-4">
                                <h5><i class="fas fa-paperclip text-primary"></i> Прикріплені файли:</h5>
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
                    </div>
                </div>
            </div>

            <!-- Домашнє завдання -->
            @if (!empty($lesson->homework_text) || !empty(json_decode($lesson->homework_files, true)) || !empty($lesson->homework_video_url))
                <div class="col-md-5">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">
                            <h4><i class="fas fa-house-user"></i> Домашнє завдання</h4>
                        </div>
                        <div class="card-body">
                            @if (!empty($lesson->homework_text))
                                <p class="p-3 border rounded bg-light">{{ $lesson->homework_text }}</p>
                            @endif

                            @php
                                $homeworkFiles = json_decode($lesson->homework_files, true) ?? [];
                            @endphp
                            @if (!empty($homeworkFiles))
                                <h6><i class="fas fa-file-alt text-primary"></i> Прикріплені файли:</h6>
                                <ul class="list-group mb-3">
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
                                <div class="text-center">
                                    <a href="{{ $lesson->homework_video_url }}" target="_blank" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-video"></i> Відео до ДЗ
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('admin.course.show', ['course' => $lesson->course_id]) }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-circle-left"></i> Назад до курсу
            </a>
        </div>
    </div>
@endsection
