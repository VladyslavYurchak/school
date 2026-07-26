@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-translate"></i> Словник уроку</span>
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                        <p class="admin-subtitle">
                            Мова: {{ $lesson->course->language->name }}. Додавайте слова й фрази для подальшого повторення учнями.
                        </p>
                    </div>
                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.vocabulary.create', $lesson) }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i> Нове слово
                        </a>
                        <a href="{{ route('admin.course.lesson.show', $lesson) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i> До уроку
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Знайти у загальному словнику</h2>
                </div>
                <div class="admin-panel-body">
                    <form method="GET" action="{{ route('admin.course.lesson.vocabulary.index', $lesson) }}"
                          class="lesson-vocabulary-search">
                        <label for="vocabulary-search" class="visually-hidden">Слово або переклад</label>
                        <input type="search" id="vocabulary-search" name="q" class="form-control"
                               value="{{ $query }}" placeholder="Введіть слово, фразу або переклад...">
                        <button type="submit" class="admin-btn-soft"><i class="bi bi-search"></i> Знайти</button>
                    </form>

                    @if($query !== '')
                        <div class="lesson-vocabulary-search-results">
                            @forelse($searchResults as $item)
                                <div class="lesson-vocabulary-result">
                                    <div>
                                        <strong>{{ $item->term }}</strong>
                                        @if($item->transcription)<span class="text-muted">{{ $item->transcription }}</span>@endif
                                        <div class="text-muted">{{ $item->translation }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.course.lesson.vocabulary.attach', [$lesson, $item]) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn-primary" title="Прикріпити до уроку">
                                            <i class="bi bi-plus-lg"></i> Додати
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <div class="admin-empty-state py-4">
                                    <i class="bi bi-search"></i>
                                    <h3>Збігів не знайдено</h3>
                                    <p>Створіть новий словниковий запис для цього уроку.</p>
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Слова цього уроку</h2>
                    <span id="vocabulary-order-status" class="small text-muted" aria-live="polite">
                        Усього: {{ $links->count() }}
                    </span>
                </div>

                @if($links->isEmpty())
                    <div class="admin-empty-state">
                        <i class="bi bi-translate"></i>
                        <h3>Словник уроку порожній</h3>
                        <p>Створіть нове слово або знайдіть готове у загальному словнику.</p>
                    </div>
                @else
                    <div id="lesson-vocabulary-list" class="lesson-vocabulary-list"
                         data-order-url="{{ route('admin.course.lesson.vocabulary.order', $lesson) }}">
                        @foreach($links as $link)
                            @php($item = $link->vocabularyItem)
                            <div class="lesson-vocabulary-row" data-link-id="{{ $link->id }}">
                                <button type="button" class="lesson-vocabulary-handle" title="Перетягнути" aria-label="Перетягнути слово">
                                    <i class="bi bi-grip-vertical"></i>
                                </button>
                                <div class="lesson-vocabulary-term">
                                    <div>
                                        <strong>{{ $item->term }}</strong>
                                        @if($item->transcription)<span>{{ $item->transcription }}</span>@endif
                                        @if($item->part_of_speech)<span class="admin-badge admin-badge-muted">{{ $item->part_of_speech }}</span>@endif
                                        @if($link->is_required)<span class="admin-badge admin-badge-warning">Обов’язкове</span>@endif
                                    </div>
                                    <div class="lesson-vocabulary-translation">{{ $item->translation }}</div>
                                    @if($item->example)<div class="lesson-vocabulary-example">{{ $item->example }}</div>@endif
                                </div>
                                <div class="admin-row-actions lesson-vocabulary-actions">
                                    <a href="{{ route('admin.course.lesson.vocabulary.edit', [$lesson, $link]) }}"
                                       class="admin-btn-warning" title="Редагувати">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.course.lesson.vocabulary.detach', [$lesson, $link]) }}"
                                          onsubmit="return confirm('Від’єднати слово від цього уроку? Воно залишиться у загальному словнику.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn-danger" title="Від’єднати">
                                            <i class="bi bi-link-45deg"></i>
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
            const list = document.querySelector('#lesson-vocabulary-list');
            const status = document.querySelector('#vocabulary-order-status');

            if (!list || typeof Sortable === 'undefined') {
                return;
            }

            new Sortable(list, {
                animation: 160,
                handle: '.lesson-vocabulary-handle',
                ghostClass: 'lesson-content-block-ghost',
                onEnd: async function () {
                    const links = [...list.querySelectorAll('[data-link-id]')]
                        .map(row => Number(row.dataset.linkId));
                    status.textContent = 'Зберігаємо порядок...';

                    try {
                        const response = await fetch(list.dataset.orderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ links }),
                        });

                        if (!response.ok) throw new Error('Order update failed');
                        status.textContent = 'Порядок збережено';
                    } catch (error) {
                        status.textContent = 'Не вдалося зберегти порядок. Оновіть сторінку.';
                    }
                },
            });
        });
    </script>
@endpush
