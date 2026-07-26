@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-ui-checks-grid"></i>
                            Тестування
                        </span>
                        <h1 class="admin-title">Тести</h1>
                        <p class="admin-subtitle">Налаштування мовних тестів, секцій, питань і діапазонів результатів.</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.testing.tests.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Створити тест
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список тестів</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $tests->total() }}</span>
                </div>

                <div class="admin-panel-body">
                    @if($tests->count())
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table-lg">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Назва</th>
                                    <th>Мова</th>
                                    <th>Макс. бал</th>
                                    <th>Активний</th>
                                    <th>На сайті</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tests as $test)
                                    <tr>
                                        <td>{{ $test->id }}</td>
                                        <td>{{ $test->title }}</td>
                                        <td>{{ strtoupper($test->language_code) }}</td>
                                        <td>{{ $test->max_score }}</td>
                                        <td>
                                            @if($test->is_active)
                                                <span class="admin-badge admin-badge-free">Так</span>
                                            @else
                                                <span class="admin-badge admin-badge-muted">Ні</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($test->is_public)
                                                <span class="admin-badge admin-badge-free">Так</span>
                                            @else
                                                <span class="admin-badge admin-badge-muted">Ні</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="admin-actions justify-content-end">
                                                <a href="{{ route('admin.testing.tests.edit', $test) }}" class="admin-btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                    Редагувати
                                                </a>
                                                <a href="{{ route('admin.testing.tests.sections.index', $test) }}" class="admin-btn-soft">Секції</a>
                                                <a href="{{ route('admin.testing.tests.questions.index', $test) }}" class="admin-btn-soft">Питання</a>
                                                <a href="{{ route('admin.testing.tests.result-ranges.index', $test) }}" class="admin-btn-soft">Результати</a>
                                                <form action="{{ route('admin.testing.tests.destroy', $test) }}" method="POST" onsubmit="return confirm('Видалити тест?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="admin-btn-danger">
                                                        <i class="bi bi-trash"></i>
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

                        <div class="mt-3">
                            {{ $tests->onEachSide(2)->links('admin.pagination.pagination') }}
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-ui-checks-grid"></i>
                            </div>
                            <h3>Тестів поки немає</h3>
                            <p>Створіть перший тест, а потім додайте секції, питання і результати.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
