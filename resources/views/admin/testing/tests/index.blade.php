@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h1 class="mb-0">Тести</h1>

                <a href="{{ route('admin.testing.tests.create') }}" class="btn btn-custom">
                    Створити тест
                </a>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>Мова</th>
                            <th>Макс. бал</th>
                            <th>Активний</th>
                            <th class="text-end">Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($tests as $test)
                            <tr>
                                <td>{{ $test->id }}</td>
                                <td>{{ $test->title }}</td>
                                <td>{{ $test->language_code }}</td>
                                <td>{{ $test->max_score }}</td>
                                <td>{{ $test->is_active ? 'Так' : 'Ні' }}</td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <a href="{{ route('admin.testing.tests.edit', $test) }}"
                                           class="btn btn-sm btn-primary">
                                            Редагувати
                                        </a>

                                        <a href="{{ route('admin.testing.tests.sections.index', $test) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            Секції
                                        </a>

                                        <a href="{{ route('admin.testing.tests.questions.index', $test) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            Питання
                                        </a>

                                        <a href="{{ route('admin.testing.tests.result-ranges.index', $test) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            Результати
                                        </a>

                                        <form action="{{ route('admin.testing.tests.destroy', $test) }}"
                                              method="POST"
                                              onsubmit="return confirm('Видалити тест?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Видалити
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Тестів поки немає
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{ $tests->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
