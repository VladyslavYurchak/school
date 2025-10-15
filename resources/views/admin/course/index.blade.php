@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-white d-flex align-items-center">
                <h3 class="fw-bold text-dark mb-0">Курси</h3>
                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('admin.course.create') }}" class="btn btn-primary shadow-sm">+ Курс</a>
                    <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#addLanguageModal">+ Мова</button>
                </div>
            </div>



            <div class="card-body">
                <!-- Повідомлення про успіх -->
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <!-- Фільтр по мові -->
                <form action="{{ route('admin.course.index') }}" method="GET" class="mb-3">
                    <div class="input-group">
                        <label class="input-group-text">Оберіть мову:</label>
                        <select name="language" class="form-select" onchange="this.form.submit()">
                            <option value="">Всі мови</option>
                            @foreach($languages as $language)
                                <option value="{{ $language->id }}" {{ request('language') == $language->id ? 'selected' : '' }}>
                                    {{ $language->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <!-- Опції для показу/сховування колонок -->
                <div class="mb-3">
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#columnsCollapse" aria-expanded="false" aria-controls="columnsCollapse">
                        Виберіть стовпці
                    </button>

                    <div class="collapse mt-2" id="columnsCollapse">
                        <ul class="list-group">
                            @php
                                $columns = [
                                    'id' => 'ID',
                                    'name' => 'Назва курсу',
                                    'language' => 'Мова',
                                    'price' => 'Ціна',
                                    'lessons' => 'Уроків',
                                    'status' => 'Статус',
                                    'actions' => 'Дія'
                                ];
                            @endphp
                            @foreach($columns as $key => $label)
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="form-check m-0">
                                        <input class="form-check-input toggle-column" type="checkbox" id="toggle-{{ $key }}" data-column="{{ $key }}" checked>
                                        <label class="form-check-label ms-2" for="toggle-{{ $key }}">{{ $label }}</label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>



                <!-- Таблиця курсів -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="courses-table">
                        <thead class="table-light">
                        <tr>
                            <th class="column-id">#</th>
                            <th class="column-name">Назва курсу</th>
                            <th class="column-language">Мова</th>
                            <th class="column-price">Ціна</th>
                            <th class="column-lessons">Уроків</th>
                            <th class="column-status">Статус</th>
                            <th class="column-actions">Дія</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($courses as $course)
                            <tr>
                                <td class="column-id">{{ $course->id }}</td>
                                <td class="column-name">
                                    <a href="{{ route('admin.course.show', $course->id) }}" class="text-decoration-none">
                                        {{ $course->title }}
                                    </a>
                                </td>
                                <td class="column-language">{{ $course->language->name }}</td>
                                <td class="column-price">
                                    {{ $course->price > 0 ? $course->price . ' грн' : 'Безкоштовний' }}
                                </td>
                                <td class="column-lessons">{{ $course->lessons_count }}</td>
                                <td class="column-status">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input toggle-status"
                                            type="checkbox"
                                            data-id="{{ $course->id }}"
                                            {{ $course->is_published ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label">
                                            {{ $course->is_published ? 'Опублікований' : 'Неопублікований' }}
                                        </label>
                                    </div>
                                </td>
                                <td class="column-actions">
                                    <a href="{{ route('admin.course.edit', $course->id) }}" class="btn btn-warning btn-sm">Редагувати</a>
                                    <form action="{{ route('admin.course.delete', $course->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ви впевнені?')">Видалити</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Пагінація -->
                <div class="d-flex justify-content-between">
                    <div>{{ $courses->onEachSide(2)->links('admin.pagination.pagination') }}</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal для додавання мови -->
    <div class="modal fade" id="addLanguageModal" tabindex="-1" aria-labelledby="addLanguageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.language.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Додати мову</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="name" class="form-control" placeholder="Введіть мову" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                    <button type="submit" class="btn btn-success">Додати</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.toggle-column');
            const table = document.getElementById('courses-table');

            // Відновлення стану з LocalStorage
            checkboxes.forEach(checkbox => {
                const column = checkbox.dataset.column;
                const isVisible = localStorage.getItem(`column_${column}`);
                if (isVisible === 'false') {
                    checkbox.checked = false;
                    toggleColumnVisibility(column, false);
                }
            });

            // Подія зміни видимості
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const column = this.dataset.column;
                    const isChecked = this.checked;
                    localStorage.setItem(`column_${column}`, isChecked);
                    toggleColumnVisibility(column, isChecked);
                });
            });

            function toggleColumnVisibility(column, isVisible) {
                const columnElements = table.querySelectorAll(`.column-${column}`);
                columnElements.forEach(el => {
                    el.style.display = isVisible ? '' : 'none';
                });
            }

            // Перемикач статусу
            document.querySelectorAll('.toggle-status').forEach(toggle => {
                toggle.addEventListener('change', function () {
                    const courseId = this.dataset.id;
                    const isPublished = this.checked;
                    const label = this.nextElementSibling;

                    fetch(`/admin/courses/${courseId}/publish`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ is_published: isPublished ? 1 : 0 }),
                    })
                        .then(res => res.json())
                        .then(data => {
                            label.textContent = isPublished ? 'Опублікований' : 'Неопублікований';
                        })
                        .catch(() => {
                            alert('Сталася помилка при оновленні статусу.');
                            this.checked = !isPublished;
                            label.textContent = !isPublished ? 'Опублікований' : 'Неопублікований';
                        });
                });
            });
        });
    </script>
@endsection
