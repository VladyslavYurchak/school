@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-journal-text"></i>
                            Урок №{{ $lesson->position }}
                        </span>
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                        <p class="admin-subtitle">
                            Курс: {{ $lesson->course->title }}
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.edit', $lesson->id) }}" class="admin-btn-primary">
                            <i class="bi bi-pencil"></i>
                            Редагувати
                        </a>
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До курсу
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Основна частина</h2>
                    <a href="{{ route('admin.course.lesson.blocks.index', $lesson) }}" class="admin-btn-soft">
                        <i class="bi bi-layers"></i> Відкрити конструктор
                    </a>
                </div>

                <div class="admin-panel-body">
                    @forelse($blocks as $block)
                        <div class="admin-content-box mb-3 {{ $block->is_active ? '' : 'opacity-50' }}">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <div>
                                    @if($block->title)<h3 class="h5 mb-1">{{ $block->title }}</h3>@endif
                                    <span class="admin-badge admin-badge-muted">{{ strtoupper($block->type) }}</span>
                                </div>
                                @unless($block->is_active)<span class="admin-badge admin-badge-warning">Приховано</span>@endunless
                            </div>

                            @if($block->type === 'video')
                                <div class="ratio ratio-16x9 admin-block-preview-media">
                                    <iframe src="{{ $block->video_url }}" title="{{ $block->title ?: 'YouTube video' }}" allowfullscreen></iframe>
                                </div>
                            @elseif($block->type === 'audio')
                                <audio controls class="w-100" src="{{ asset('storage/' . $block->media_path) }}"></audio>
                            @elseif($block->type === 'image')
                                <img src="{{ asset('storage/' . $block->media_path) }}" alt="{{ $block->title ?: $block->media_name }}" class="admin-block-preview-image">
                            @elseif($block->type === 'pdf')
                                <a href="{{ asset('storage/' . $block->media_path) }}" target="_blank" rel="noopener" class="admin-file-link">
                                    <i class="bi bi-file-earmark-pdf"></i> {{ $block->media_name }}
                                </a>
                            @endif

                            @if($block->content)
                                <div class="admin-rich-content mt-3">{!! $block->content !!}</div>
                            @endif
                        </div>
                    @empty
                        @if($lesson->content)
                            <div class="admin-content-box mb-3">
                                {!! $lesson->content !!}
                            </div>
                            <div class="form-text">Старий формат матеріалу. Нові частини додавайте через конструктор.</div>
                        @else
                            <div class="admin-empty-state py-4">
                                <i class="bi bi-layers"></i>
                                <h3>Основна частина порожня</h3>
                                <p>Додайте матеріали через конструктор уроку.</p>
                            </div>
                        @endif
                    @endforelse
                </div>
            </section>

            @if($tests->count() > 0)
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <h2 class="admin-panel-title">Тест</h2>
                        <span class="admin-badge admin-badge-muted">Питань: {{ $tests->count() }}</span>
                    </div>

                    <div class="admin-panel-body">
                        @foreach($tests as $index => $test)
                            <div class="admin-content-box mb-3">
                                <p class="fw-bold mb-3">{{ $index + 1 }}. {{ $test->question }}</p>

                                @foreach($test->options as $option)
                                    <div class="form-check">
                                        <input type="{{ $test->is_multiple_choice ? 'checkbox' : 'radio' }}"
                                               class="form-check-input"
                                               disabled
                                               @if($option->is_correct) checked @endif>
                                        <label class="form-check-label">
                                            {{ $option->option_text }}
                                            @if($option->is_correct)
                                                <span class="admin-badge admin-badge-free ms-2">Правильна відповідь</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if(!empty($lesson->homework_text) || !empty($homeworkFiles) || !empty($lesson->homework_video_url))
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <h2 class="admin-panel-title">Домашнє завдання</h2>
                    </div>

                    <div class="admin-panel-body">
                        @if(!empty($lesson->homework_text))
                            <div class="admin-content-box mb-3">
                                {!! $lesson->homework_text !!}
                            </div>
                        @endif

                        @if(!empty($lesson->homework_video_url))
                            <a href="{{ $lesson->homework_video_url }}" class="admin-btn-soft" target="_blank" rel="noopener">
                                <i class="bi bi-play-circle"></i>
                                Переглянути відео до домашнього
                            </a>
                        @endif

                        @if(!empty($homeworkFiles))
                            <div class="admin-content-box mt-3">
                                <strong>Файли до домашнього:</strong>
                                <ul class="admin-file-list mt-2">
                                    @foreach ($homeworkFiles as $file)
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
                </section>
            @endif
        </div>
    </div>
@endsection
