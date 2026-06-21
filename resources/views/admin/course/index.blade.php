@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-mortarboard"></i>
                            Онлайн-навчання
                        </span>
                        <h1 class="admin-title">Курси</h1>
                        <p class="admin-subtitle">
                            Керуйте курсами, мовами, публікацією та доступними уроками.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Курс
                        </a>
                        <button class="admin-btn-soft" type="button" data-bs-toggle="modal" data-bs-target="#addLanguageModal">
                            <i class="bi bi-translate"></i>
                            Мова
                        </button>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Фільтри</h2>
                </div>

                <div class="admin-panel-body">
                    <form action="{{ route('admin.course.index') }}" method="GET" class="admin-filter-grid">
                        <div class="admin-field">
                            <label for="course-language">Мова</label>
                            <select id="course-language" name="language" class="form-select" onchange="this.form.submit()">
                                <option value="">Всі мови</option>
                                @foreach($languages as $language)
                                    <option value="{{ $language->id }}" {{ request('language') == $language->id ? 'selected' : '' }}>
                                        {{ $language->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button class="admin-btn-soft" type="button" data-bs-toggle="collapse" data-bs-target="#columnsCollapse" aria-expanded="false" aria-controls="columnsCollapse">
                            <i class="bi bi-layout-three-columns"></i>
                            Стовпці
                        </button>
                    </form>

                    <div class="collapse mt-3" id="columnsCollapse">
                        <div class="admin-columns-grid">
                            @php
                                $columns = [
                                    'id' => 'ID',
                                    'name' => 'Назва курсу',
                                    'language' => 'Мова',
                                    'price' => 'Ціна',
                                    'lessons' => 'Уроків',
                                    'status' => 'Статус',
                                    'actions' => 'Дія',
                                ];
                            @endphp

                            @foreach($columns as $key => $label)
                                <label class="admin-column-check" for="toggle-{{ $key }}">
                                    <input class="form-check-input toggle-column" type="checkbox" id="toggle-{{ $key }}" data-column="{{ $key }}" checked>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список курсів</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $courses->total() }}</span>
                </div>

                <div class="admin-panel-body p-0">
                    @if($courses->count())
                        <div class="admin-table-wrap border-0 rounded-0">
                            <table class="table admin-table mb-0" id="courses-table">
                                <thead>
                                <tr>
                                    <th class="column-id">#</th>
                                    <th class="column-name">Назва курсу</th>
                                    <th class="column-language">Мова</th>
                                    <th class="column-price">Ціна</th>
                                    <th class="column-lessons">Уроків</th>
                                    <th class="column-status">Статус</th>
                                    <th class="column-actions text-end">Дія</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($courses as $course)
                                    <tr>
                                        <td class="column-id">{{ $course->id }}</td>
                                        <td class="column-name">
                                            <a href="{{ route('admin.course.show', $course->id) }}" class="admin-course-link">
                                                {{ $course->title }}
                                            </a>
                                        </td>
                                        <td class="column-language">{{ $course->language->name }}</td>
                                        <td class="column-price">
                                            @if($course->price > 0)
                                                {{ $course->price }} грн
                                            @else
                                                <span class="admin-badge admin-badge-free">Безкоштовний</span>
                                            @endif
                                        </td>
                                        <td class="column-lessons">{{ $course->lessons_count }}</td>
                                        <td class="column-status">
                                            <div class="form-check form-switch admin-switch">
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
                                        <td class="column-actions text-end">
                                            <div class="admin-row-actions">
                                                <a href="{{ route('admin.course.edit', $course->id) }}" class="btn btn-sm btn-outline-primary">
                                                    Редагувати
                                                </a>
                                                <form action="{{ route('admin.course.delete', $course->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Ви впевнені?')">
                                                        Видалити
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3">
                            {{ $courses->onEachSide(2)->links('admin.pagination.pagination') }}
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <i class="bi bi-mortarboard"></i>
                            <h3>Курсів поки немає</h3>
                            <p>Створіть перший курс, щоб додавати уроки та продавати їх студентам.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="addLanguageModal" tabindex="-1" aria-labelledby="addLanguageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.language.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addLanguageModalLabel">Додати мову</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="language-name" class="form-label">Назва мови</label>
                    <input type="text" name="name" id="language-name" class="form-control" placeholder="Наприклад: Англійська" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn-soft" data-bs-dismiss="modal">Закрити</button>
                    <button type="submit" class="admin-btn-primary">Додати</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.toggle-column');
            const table = document.getElementById('courses-table');

            if (!table) {
                return;
            }

            checkboxes.forEach(checkbox => {
                const column = checkbox.dataset.column;
                const isVisible = localStorage.getItem(`column_${column}`);
                if (isVisible === 'false') {
                    checkbox.checked = false;
                    toggleColumnVisibility(column, false);
                }
            });

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
                        .then(() => {
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
