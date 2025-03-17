@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card">
            <div class="card-header">
                <h3>Курси</h3>
            </div>
            <div class="card-body">

                <!-- Повідомлення про успіх -->
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Форма для додавання нової мови -->
                <form action="{{ route('admin.language.store') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="name" class="form-control" placeholder="Введіть мову" required>
                        <button type="submit" class="btn btn-success">Додати</button>
                    </div>
                </form>

                <!-- Вибір мови -->
                <form action="{{ route('admin.course.index') }}" method="GET" class="mb-3">
                    <div class="input-group">
                        <label class="me-2">Оберіть мову курсу:</label>
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
                    <label>Виберіть стовпці для відображення:</label>
                    <div class="d-flex flex-wrap">
                        <div class="form-check me-3">
                            <input class="form-check-input toggle-column" type="checkbox" id="toggle-id" data-column="id" checked>
                            <label class="form-check-label" for="toggle-id">ID</label>
                        </div>
                        <div class="form-check me-3">
                            <input class="form-check-input toggle-column" type="checkbox" id="toggle-name" data-column="name" checked>
                            <label class="form-check-label" for="toggle-name">Назва курсу</label>
                        </div>
                        <div class="form-check me-3">
                            <input class="form-check-input toggle-column" type="checkbox" id="toggle-language" data-column="language" checked>
                            <label class="form-check-label" for="toggle-language">Мова</label>
                        </div>
                        <div class="form-check me-3">
                            <input class="form-check-input toggle-column" type="checkbox" id="toggle-price" data-column="price" checked>
                            <label class="form-check-label" for="toggle-price">Ціна</label>
                        </div>
                        <div class="form-check me-3">
                            <input class="form-check-input toggle-column" type="checkbox" id="toggle-lessons" data-column="lessons" checked>
                            <label class="form-check-label" for="toggle-lessons">Уроків</label>
                        </div>
                        <div class="form-check me-3">
                            <input class="form-check-input toggle-column" type="checkbox" id="toggle-status" data-column="status" checked>
                            <label class="form-check-label" for="toggle-status">Статус</label>
                        </div>
                        <div class="form-check me-3">
                            <input class="form-check-input toggle-column" type="checkbox" id="toggle-actions" data-column="actions" checked>
                            <label class="form-check-label" for="toggle-actions">Дія</label>
                        </div>
                    </div>
                </div>

                <!-- Кнопка створення курсу -->
                <a href="{{ route('admin.course.create') }}" class="btn btn-primary mb-3">Створити новий курс</a>

                <!-- Таблиця курсів -->
                <table class="table table-bordered" id="courses-table">
                    <thead>
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
                            <td class="column-name"><a href="{{ route('admin.course.show', $course->id) }}">{{ $course->title }}</a></td>
                            <td class="column-language">{{ $course->language->name }}</td>
                            <td class="column-price">{{ $course->price > 0 ? $course->price . ' грн' : 'Безкоштовний' }}</td>
                            <td class="column-lessons">{{ $course->lessons_count }}</td>
                            <td class="column-status">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input toggle-status"
                                        type="checkbox"
                                        data-id="{{ $course->id }}"
                                        {{ $course->is_published ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label">{{ $course->is_published ? 'Опублікований' : 'Неопублікований' }}</label>
                                </div>
                            </td>                            <td class="column-actions">
                                <a href="{{ route('admin.course.edit', $course->id) }}" class="btn btn-warning btn-sm">Редагувати</a>
                                <form action="{{ route('admin.course.delete', $course->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ви впевнені, що хочете видалити цей курс?');">Видалити</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <!-- Пагінація -->
                <div class="d-flex justify-content-between">
                    <div>
                        {{ $courses->onEachSide(2)->links('admin.pagination.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.toggle-column');
            const table = document.getElementById('courses-table');

            // Відновлення стану з LocalStorage
            checkboxes.forEach(checkbox => {
                const column = checkbox.dataset.column;
                const isVisible = localStorage.getItem(`column_${column}`) === 'true';

                if (isVisible === false || isVisible === 'false') {
                    checkbox.checked = false;
                    toggleColumnVisibility(column, false);
                }
            });

            // Слухач подій для чекбоксів
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const column = this.dataset.column;
                    const isChecked = this.checked;
                    localStorage.setItem(`column_${column}`, isChecked);
                    toggleColumnVisibility(column, isChecked);
                });
            });

            // Функція для переключення видимості стовпців
            function toggleColumnVisibility(column, isVisible) {
                const columnElements = table.querySelectorAll(`.column-${column}`);
                columnElements.forEach(el => {
                    el.classList.toggle('hidden-column', !isVisible);
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleSwitches = document.querySelectorAll('.toggle-status');

            toggleSwitches.forEach(toggle => {
                toggle.addEventListener('change', function () {
                    const courseId = this.dataset.id;
                    const isPublished = this.checked;

                    // Динамічне оновлення тексту "Опублікований/Неопублікований"
                    const label = this.nextElementSibling;
                    label.textContent = isPublished ? 'Опублікований' : 'Неопублікований';

                    // Відправка запиту на сервер
                    fetch(`/admin/courses/${courseId}/publish`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ is_published: isPublished }),
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Не вдалося оновити статус');
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            alert('Сталася помилка під час оновлення статусу.');
                            this.checked = !isPublished; // Повернення бігунка у попереднє положення при помилці
                            label.textContent = !isPublished ? 'Опублікований' : 'Неопублікований';
                        });
                });
            });
        });
    </script>

    <style>
        .hidden-column {
            display: none;
        }
    </style>
@endsection
