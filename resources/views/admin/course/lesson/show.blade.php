@extends('admin.layouts.layout')

@section('content')
    <div class="container my-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Курс "{{ $lesson->course->title }}"</h2>
            <p class="lead">Урок №{{ $lesson->position }} — <strong>{{ $lesson->title }}</strong></p>
        </div>
        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="btn btn-secondary btn-sm">
            ← Назад
        </a>

        {{-- Основна частина --}}
        <div class="card shadow mb-5">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-book-open"></i> Основна частина</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    {!! nl2br(e($lesson->content)) !!}
                </div>

                @if(!empty($lesson->audio_file))
                    <div class="mb-3">
                        <label class="form-label fw-bold">Аудіо:</label>
                        <audio controls class="w-100">
                            <source src="{{ asset('storage/' . $lesson->audio_file) }}" type="audio/mpeg">
                            Ваш браузер не підтримує аудіо.
                        </audio>
                    </div>
                @endif

                @if($lesson->video_url)
                    <div class="text-center mb-3">
                        <a href="{{ $lesson->video_url }}" class="btn btn-outline-primary" target="_blank">
                            🎥 Переглянути відео
                        </a>
                    </div>
                @endif

                @if(!empty($mediaFiles))
                    <div class="mt-3">
                        <label class="form-label fw-bold">Додаткові матеріали:</label>
                        <ul class="list-group">
                            @foreach ($mediaFiles as $file)
                                <li class="list-group-item">
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank" class="text-primary">
                                        📎 {{ basename($file) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        {{-- Тестова частина --}}
        {{-- Тестова частина --}}
        @if($tests->count() > 0)
            <div class="card shadow mb-5">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-question-circle"></i> Тест</h4>
                </div>
                <div class="card-body">
                    @foreach($tests as $index => $test)
                        <div class="mb-4">
                            <p class="fw-bold">{{ $index + 1 }}. {{ $test->question }}</p>

                            @foreach($test->options as $option)
                                <div class="form-check">
                                    <input type="{{ $test->is_multiple_choice ? 'checkbox' : 'radio' }}"
                                           class="form-check-input"
                                           disabled
                                           @if($option->is_correct) checked @endif>
                                    <label class="form-check-label">
                                        {{ $option->option_text }}
                                        @if($option->is_correct)
                                            <span class="badge bg-success ms-2">Правильна відповідь</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    <p class="text-muted small">* Показано правильні відповіді. Ваша участь у тесті ще не врахована.</p>
                </div>
            </div>
        @endif


        {{-- Домашнє завдання --}}
        @if(!empty($lesson->homework_text) || !empty($homeworkFiles) || !empty($lesson->homework_video_url))
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h4 class="mb-0"><i class="fas fa-home"></i> Домашнє завдання</h4>
                </div>
                <div class="card-body">
                    @if(!empty($lesson->homework_text))
                        <div class="mb-3">
                            {!! nl2br(e($lesson->homework_text)) !!}
                        </div>
                    @endif

                    @if(!empty($lesson->homework_video_url))
                        <div class="text-center mb-3">
                            <a href="{{ $lesson->homework_video_url }}" class="btn btn-outline-primary" target="_blank">
                                🎞️ Переглянути відео до домашки
                            </a>
                        </div>
                    @endif

                    @if(!empty($homeworkFiles))
                        <div class="mt-3">
                            <label class="form-label fw-bold">Файли до домашки:</label>
                            <ul class="list-group">
                                @foreach ($homeworkFiles as $file)
                                    <li class="list-group-item">
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="text-primary">
                                            📎 {{ basename($file) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
