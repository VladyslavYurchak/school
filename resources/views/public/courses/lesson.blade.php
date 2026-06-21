@extends('public.layouts.main')

@section('content')
    <div class="lesson-page">
        <header class="lesson-header">
            <div class="container">
                <a href="{{ route('courses.show', $course) }}" class="lesson-back">
                    <i class="bi bi-arrow-left"></i>
                    {{ $course->title }}
                </a>
                <div class="lesson-heading-row">
                    <div>
                        <div class="lesson-kicker">Урок {{ $lesson->position }}</div>
                        <h1 class="lesson-title">{{ $lesson->title }}</h1>
                        @if($lesson->description)
                            <p class="lesson-description">{{ $lesson->description }}</p>
                        @endif
                    </div>
                    <span class="lesson-progress-label">
                        {{ $course->lessons->search(fn ($item) => $item->id === $lesson->id) + 1 }} / {{ $course->lessons->count() }}
                    </span>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="lesson-layout">
                <main class="lesson-main">
                    <div class="lesson-materials" aria-label="Матеріали уроку">
                        @forelse($lesson->contentBlocks as $block)
                            <section class="lesson-material-block lesson-material-block--{{ $block->type }}">
                                <header class="lesson-material-header">
                                    <span class="lesson-material-icon" aria-hidden="true">
                                        <i class="bi {{ match($block->type) {
                                            'video' => 'bi-play-circle',
                                            'audio' => 'bi-volume-up',
                                            'image' => 'bi-image',
                                            'pdf' => 'bi-file-earmark-pdf',
                                            default => 'bi-text-paragraph',
                                        } }}"></i>
                                    </span>
                                    <div>
                                        <div class="lesson-material-kind">
                                            {{ match($block->type) {
                                                'video' => 'Відео',
                                                'audio' => 'Аудіо',
                                                'image' => 'Зображення',
                                                'pdf' => 'PDF-матеріал',
                                                default => 'Матеріал',
                                            } }}
                                        </div>
                                        @if($block->title)
                                            <h2 class="lesson-material-title">{{ $block->title }}</h2>
                                        @endif
                                    </div>
                                </header>

                                @if($block->type === 'video')
                                    <div class="lesson-video-frame ratio ratio-16x9">
                                        <iframe
                                            src="{{ $block->video_url }}"
                                            title="{{ $block->title ?: 'Відео уроку' }}"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                        ></iframe>
                                    </div>
                                @elseif($block->type === 'audio')
                                    <div class="lesson-audio-player">
                                        <audio controls preload="metadata" src="{{ asset('storage/' . $block->media_path) }}"></audio>
                                        <a href="{{ asset('storage/' . $block->media_path) }}"
                                           class="lesson-download-link" download>
                                            <i class="bi bi-download"></i>
                                            Завантажити аудіо
                                        </a>
                                    </div>
                                @elseif($block->type === 'image')
                                    <a href="{{ asset('storage/' . $block->media_path) }}"
                                       class="lesson-image-link" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . $block->media_path) }}"
                                             alt="{{ $block->title ?: $block->media_name }}"
                                             class="lesson-material-image" loading="lazy">
                                    </a>
                                @elseif($block->type === 'pdf')
                                    <div class="lesson-pdf-preview">
                                        <iframe src="{{ asset('storage/' . $block->media_path) }}"
                                                title="{{ $block->title ?: $block->media_name }}"></iframe>
                                    </div>
                                    <div class="lesson-file-actions">
                                        <a href="{{ asset('storage/' . $block->media_path) }}"
                                           class="lesson-file-button" target="_blank" rel="noopener">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                            Відкрити PDF
                                        </a>
                                        <a href="{{ asset('storage/' . $block->media_path) }}"
                                           class="lesson-download-link" download>
                                            <i class="bi bi-download"></i>
                                            Завантажити
                                        </a>
                                    </div>
                                @endif

                                @if($block->content)
                                    <div class="lesson-prose {{ $block->type === 'text' ? '' : 'lesson-prose--description' }}">
                                        {!! $block->content !!}
                                    </div>
                                @endif
                            </section>
                        @empty
                            @if($lesson->content || $lesson->video_url || $lesson->audio_file || !empty($lesson->media_files))
                                @if($lesson->video_url)
                                    <section class="lesson-material-block lesson-material-block--video">
                                        <div class="lesson-video-frame ratio ratio-16x9">
                                            <iframe src="{{ $lesson->video_url }}" title="Відео уроку" allowfullscreen></iframe>
                                        </div>
                                    </section>
                                @endif

                                @if($lesson->audio_file)
                                    <section class="lesson-material-block lesson-material-block--audio">
                                        <audio controls preload="metadata" src="{{ asset('storage/' . $lesson->audio_file) }}"></audio>
                                    </section>
                                @endif

                                @if($lesson->content)
                                    <section class="lesson-material-block lesson-material-block--text">
                                        <div class="lesson-prose">{!! $lesson->content !!}</div>
                                    </section>
                                @endif
                            @else
                                <div class="lesson-empty-materials">
                                    <i class="bi bi-journal-text"></i>
                                    <p>Матеріали цього уроку ще готуються.</p>
                                </div>
                            @endif
                        @endforelse
                    </div>

                    @if($lesson->tests->count())
                        <section class="lesson-system-block lesson-system-block--tests">
                            <div class="lesson-system-heading">
                                <span class="lesson-system-icon"><i class="bi bi-ui-checks"></i></span>
                                <div>
                                    <div class="lesson-system-kicker">Перевірка знань</div>
                                    <h2>Підсумковий тест</h2>
                                </div>
                            </div>

                            @if($lastTestAttempt)
                                <div class="lesson-test-result {{ $lastTestAttempt->passed ? 'is-passed' : 'is-failed' }}">
                                    <strong>Останній результат: {{ $lastTestAttempt->score }} / {{ $lastTestAttempt->max_score }}</strong>
                                    <span>{{ $lastTestAttempt->percent }}% · {{ $lastTestAttempt->finished_at?->format('d.m.Y H:i') }}</span>
                                </div>
                            @endif

                            <form action="{{ route('courses.lessons.tests.submit', [$course, $lesson]) }}" method="POST">
                                @csrf
                                <div class="lesson-tests">
                                    @foreach($lesson->tests as $i => $test)
                                        <fieldset class="lesson-test-item">
                                            <legend class="lesson-test-question">
                                                <span class="lesson-test-num">{{ $i + 1 }}</span>
                                                <span>{{ $test->question }}</span>
                                            </legend>

                                            @if($test->options->count())
                                                <div class="lesson-test-options">
                                                    @foreach($test->options as $option)
                                                        <label class="lesson-test-option">
                                                            <input type="{{ $test->is_multiple_choice ? 'checkbox' : 'radio' }}"
                                                                   name="answers[{{ $test->id }}]{{ $test->is_multiple_choice ? '[]' : '' }}"
                                                                   value="{{ $option->id }}">
                                                            <span>{{ $option->option_text }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @else
                                                <input class="lesson-test-text-input" type="text"
                                                       name="answers[{{ $test->id }}]" placeholder="Ваша відповідь...">
                                            @endif
                                        </fieldset>
                                    @endforeach
                                </div>

                                <button type="submit" class="lesson-submit-button">
                                    <i class="bi bi-check2-circle"></i>
                                    {{ $lastTestAttempt ? 'Пройти ще раз' : 'Завершити тест' }}
                                </button>
                            </form>
                        </section>
                    @endif

                    @if($lesson->homework_text || $lesson->homework_video_url || !empty($lesson->homework_files))
                        <section class="lesson-system-block lesson-system-block--homework">
                            <div class="lesson-system-heading">
                                <span class="lesson-system-icon"><i class="bi bi-pencil-square"></i></span>
                                <div>
                                    <div class="lesson-system-kicker">Після уроку</div>
                                    <h2>Домашнє завдання</h2>
                                </div>
                            </div>

                            @if($lesson->homework_text)
                                <div class="lesson-prose">{!! $lesson->homework_text !!}</div>
                            @endif

                            @if($lesson->homework_video_url)
                                <div class="lesson-video-frame ratio ratio-16x9 mt-3">
                                    <iframe src="{{ $lesson->homework_video_url }}" title="Відео до домашнього завдання" allowfullscreen></iframe>
                                </div>
                            @endif

                            @if(!empty($lesson->homework_files))
                                <div class="lesson-files-list mt-3">
                                    @foreach($lesson->homework_files as $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener" class="lesson-file-item">
                                            <i class="bi bi-paperclip"></i>
                                            <span>{{ basename($file) }}</span>
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif
                </main>

                <aside class="lesson-sidebar">
                    <div class="lesson-sidebar-inner">
                        <div class="lesson-sidebar-title">Уроки курсу</div>
                        <nav class="lesson-nav">
                            @foreach($course->lessons as $courseLesson)
                                <a href="{{ route('courses.lessons.show', [$course, $courseLesson]) }}"
                                   class="lesson-nav-item {{ $courseLesson->id === $lesson->id ? 'lesson-nav-item--active' : '' }}">
                                    <span class="lesson-nav-num">{{ $courseLesson->position }}</span>
                                    <span class="lesson-nav-name">{{ $courseLesson->title }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
