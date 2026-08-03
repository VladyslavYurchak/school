@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-puzzle"></i> Інтерактивні вправи</span>
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                        <p class="admin-subtitle">Створюйте вправи після матеріалів уроку та змінюйте їх порядок перетягуванням.</p>
                    </div>
                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.show', $lesson) }}" class="admin-btn-soft">
                            <i class="bi bi-eye"></i> Перегляд уроку
                        </a>
                        <a href="{{ route('admin.course.lesson.exercises.create', ['lesson' => $lesson, 'type' => 'matching']) }}" class="admin-btn-soft">
                            <i class="bi bi-intersect"></i> З’єднати пари
                        </a>
                        <a href="{{ route('admin.course.lesson.exercises.create', ['lesson' => $lesson, 'type' => 'fill_blank']) }}" class="admin-btn-primary">
                            <i class="bi bi-input-cursor-text"></i> Заповнити пропуски
                        </a>
                        <a href="{{ route('admin.course.lesson.exercises.create', ['lesson' => $lesson, 'type' => 'word_order']) }}" class="admin-btn-primary">
                            <i class="bi bi-sort-down"></i> Скласти речення
                        </a>
                        <a href="{{ route('admin.course.lesson.exercises.create', ['lesson' => $lesson, 'type' => 'transformation']) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-repeat"></i> Трансформація
                        </a>
                        <a href="{{ route('admin.course.lesson.exercises.create', ['lesson' => $lesson, 'type' => 'true_false']) }}" class="admin-btn-soft">
                            <i class="bi bi-check2-square"></i> Правда / неправда
                        </a>
                        <a href="{{ route('admin.course.lesson.exercises.create', ['lesson' => $lesson, 'type' => 'dictation']) }}" class="admin-btn-soft">
                            <i class="bi bi-volume-up"></i> Диктант
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Вправи уроку</h2>
                    <span id="lesson-exercise-order-status" class="small text-muted" aria-live="polite"></span>
                </div>

                @if($exercises->isEmpty())
                    <div class="admin-empty-state">
                        <i class="bi bi-intersect"></i>
                        <h3>Вправ ще немає</h3>
                        <p>Почніть із вправи, де учень з’єднує слова з перекладами або відповідями.</p>
                    </div>
                @else
                    <div id="lesson-exercise-list"
                         class="lesson-exercise-admin-list"
                         data-order-url="{{ route('admin.course.lesson.exercises.order', $lesson) }}">
                        @foreach($exercises as $exercise)
                            @php
                                $isFillBlank = $exercise->type === \App\Models\LessonExercise::TYPE_FILL_BLANK;
                                $isWordOrder = $exercise->type === \App\Models\LessonExercise::TYPE_WORD_ORDER;
                                $isTransformation = $exercise->type === \App\Models\LessonExercise::TYPE_TRANSFORMATION;
                                $isTrueFalse = $exercise->type === \App\Models\LessonExercise::TYPE_TRUE_FALSE;
                                $isDictation = $exercise->type === \App\Models\LessonExercise::TYPE_DICTATION;
                                $typeLabel = match (true) {
                                    $isFillBlank => 'Заповнити пропуски',
                                    $isWordOrder => 'Скласти речення',
                                    $isTransformation => 'Трансформація речення',
                                    $isTrueFalse => 'Правда / неправда',
                                    $isDictation => 'Диктант',
                                    default => 'З’єднати пари',
                                };
                                $typeIcon = match (true) {
                                    $isFillBlank => 'bi-input-cursor-text',
                                    $isWordOrder => 'bi-sort-down',
                                    $isTransformation => 'bi-arrow-repeat',
                                    $isTrueFalse => 'bi-check2-square',
                                    $isDictation => 'bi-volume-up',
                                    default => 'bi-intersect',
                                };
                            @endphp
                            <div class="lesson-exercise-admin-row {{ $exercise->is_active ? '' : 'is-hidden' }}"
                                 data-exercise-id="{{ $exercise->id }}">
                                <button type="button" class="lesson-exercise-drag-handle"
                                        title="Перетягнути вправу" aria-label="Перетягнути вправу">
                                    <i class="bi bi-grip-vertical"></i>
                                </button>
                                <div class="lesson-exercise-admin-icon"><i class="bi {{ $typeIcon }}"></i></div>
                                <div class="lesson-exercise-admin-summary">
                                    <div>
                                        <strong>{{ $exercise->title }}</strong>
                                        <span class="admin-badge admin-badge-muted">{{ $typeLabel }}</span>
                                        @unless($exercise->is_active)
                                            <span class="admin-badge admin-badge-warning">Приховано</span>
                                        @endunless
                                    </div>
                                    <p>{{ $exercise->description ?: 'Без додаткової інструкції' }}</p>
                                    <small>
                                        Завдань: {{ $exercise->items->count() }}
                                        @if($isFillBlank)
                                            · {{ data_get($exercise->settings, 'answer_mode') === 'choice' ? 'Вибір зі списку' : 'Введення відповіді' }}
                                        @endif
                                    </small>
                                </div>
                                <div class="admin-row-actions lesson-exercise-admin-actions">
                                    <a href="{{ route('admin.course.lesson.exercises.edit', [$lesson, $exercise]) }}"
                                       class="admin-btn-warning" title="Редагувати" aria-label="Редагувати вправу">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.course.lesson.exercises.toggle', [$lesson, $exercise]) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="admin-btn-soft"
                                                title="{{ $exercise->is_active ? 'Приховати' : 'Показати' }}"
                                                aria-label="{{ $exercise->is_active ? 'Приховати вправу' : 'Показати вправу' }}">
                                            <i class="bi {{ $exercise->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.course.lesson.exercises.destroy', [$lesson, $exercise]) }}"
                                          method="POST" onsubmit="return confirm('Видалити цю вправу?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn-danger" title="Видалити" aria-label="Видалити вправу">
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
            const list = document.querySelector('#lesson-exercise-list');
            const status = document.querySelector('#lesson-exercise-order-status');

            if (!list || typeof Sortable === 'undefined') {
                return;
            }

            new Sortable(list, {
                animation: 160,
                handle: '.lesson-exercise-drag-handle',
                ghostClass: 'lesson-exercise-admin-ghost',
                onEnd: async function () {
                    const exercises = [...list.querySelectorAll('[data-exercise-id]')]
                        .map(item => Number(item.dataset.exerciseId));

                    status.textContent = 'Зберігаємо порядок...';

                    try {
                        const response = await fetch(list.dataset.orderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ exercises }),
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
