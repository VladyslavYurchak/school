@extends('admin.layouts.layout')

@section('content')
    @php
        $types = [
            'text' => ['Текст', 'bi-text-paragraph'],
            'video' => ['YouTube-відео', 'bi-youtube'],
            'audio' => ['Аудіо', 'bi-volume-up'],
            'image' => ['Зображення', 'bi-image'],
            'pdf' => ['PDF', 'bi-file-earmark-pdf'],
        ];
    @endphp

    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-layers"></i> Конструктор уроку</span>
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                        <p class="admin-subtitle">Додавайте матеріали блоками та змінюйте їх порядок перетягуванням.</p>
                    </div>
                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.show', $lesson) }}" class="admin-btn-soft">
                            <i class="bi bi-eye"></i> Перегляд уроку
                        </a>
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i> До курсу
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Додати блок</h2>
                    <span class="admin-badge admin-badge-muted">Блоків: {{ $blocks->count() }}</span>
                </div>
                <div class="admin-panel-body">
                    <div class="lesson-block-type-actions">
                        @foreach($types as $type => [$label, $icon])
                            <a href="{{ route('admin.course.lesson.blocks.create', ['lesson' => $lesson, 'type' => $type]) }}"
                               class="lesson-block-type-button">
                                <i class="bi {{ $icon }}"></i>
                                <span>{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Основна частина уроку</h2>
                    <span id="lesson-block-order-status" class="small text-muted" aria-live="polite"></span>
                </div>

                @if($blocks->isEmpty())
                    <div class="admin-empty-state">
                        <i class="bi bi-layers"></i>
                        <h3>Матеріалів ще немає</h3>
                        <p>Додайте перший текст, відео, аудіо, зображення або PDF.</p>
                    </div>
                @else
                    <div id="lesson-content-blocks" class="lesson-content-block-list"
                         data-order-url="{{ route('admin.course.lesson.blocks.order', $lesson) }}">
                        @foreach($blocks as $block)
                            @php([$typeLabel, $typeIcon] = $types[$block->type])
                            <div class="lesson-content-block-row {{ $block->is_active ? '' : 'is-hidden' }}"
                                 data-block-id="{{ $block->id }}">
                                <button type="button" class="lesson-block-drag-handle" title="Перетягнути блок" aria-label="Перетягнути блок">
                                    <i class="bi bi-grip-vertical"></i>
                                </button>

                                <div class="lesson-block-kind"><i class="bi {{ $typeIcon }}"></i></div>

                                <div class="lesson-block-summary">
                                    <div class="lesson-block-summary-title">
                                        {{ $block->title ?: $typeLabel }}
                                        <span class="admin-badge admin-badge-muted">{{ $typeLabel }}</span>
                                        @unless($block->is_active)
                                            <span class="admin-badge admin-badge-warning">Приховано</span>
                                        @endunless
                                    </div>
                                    <div class="lesson-block-summary-text">
                                        @if($block->type === 'video')
                                            {{ $block->video_url }}
                                        @elseif($block->media_name)
                                            {{ $block->media_name }}
                                        @elseif($block->content)
                                            {{ \Illuminate\Support\Str::limit(strip_tags($block->content), 130) }}
                                        @else
                                            Опис не додано
                                        @endif
                                    </div>
                                </div>

                                <div class="admin-row-actions lesson-block-actions">
                                    <a href="{{ route('admin.course.lesson.blocks.edit', [$lesson, $block]) }}"
                                       class="admin-btn-warning" title="Редагувати">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.course.lesson.blocks.toggle', [$lesson, $block]) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="admin-btn-soft" title="{{ $block->is_active ? 'Приховати' : 'Показати' }}">
                                            <i class="bi {{ $block->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.course.lesson.blocks.destroy', [$lesson, $block]) }}"
                                          method="POST" onsubmit="return confirm('Видалити цей блок?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn-danger" title="Видалити">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.querySelector('#lesson-content-blocks');
            const status = document.querySelector('#lesson-block-order-status');

            if (!list || typeof Sortable === 'undefined') {
                return;
            }

            new Sortable(list, {
                animation: 160,
                handle: '.lesson-block-drag-handle',
                ghostClass: 'lesson-content-block-ghost',
                onEnd: async function () {
                    const blocks = [...list.querySelectorAll('[data-block-id]')]
                        .map(item => Number(item.dataset.blockId));

                    status.textContent = 'Зберігаємо порядок...';

                    try {
                        const response = await fetch(list.dataset.orderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ blocks }),
                        });

                        if (!response.ok) {
                            throw new Error('Order update failed');
                        }

                        status.textContent = 'Порядок збережено';
                    } catch (error) {
                        status.textContent = 'Не вдалося зберегти порядок. Оновіть сторінку.';
                    }
                },
            });
        });
    </script>
@endpush
