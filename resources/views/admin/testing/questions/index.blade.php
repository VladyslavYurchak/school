@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h1 class="mb-0">Питання тесту: {{ $test->title }}</h1>
                    <div class="text-muted small">Мова: {{ $test->language_code }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.testing.tests.index') }}" class="btn btn-outline-secondary">
                        До тестів
                    </a>
                    <a href="{{ route('admin.testing.tests.sections.index', $test) }}" class="btn btn-outline-secondary">
                        Секції
                    </a>
                    <a href="{{ route('admin.testing.tests.questions.create', $test) }}" class="btn btn-custom">
                        Створити питання
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Порядок</th>
                            <th>Секція</th>
                            <th>Тип</th>
                            <th>Питання</th>
                            <th>Бали</th>
                            <th>Рівень</th>
                            <th>Активне</th>
                            <th class="text-end">Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($questions as $question)
                            <tr>
                                <td>{{ $question->id }}</td>
                                <td>{{ $question->sort_order }}</td>
                                <td>{{ $question->section?->title ?? '—' }}</td>
                                <td>
                                    @php
                                        $typeLabels = [
                                            'single_choice' => 'Single choice',
                                            'multiple_choice' => 'Multiple choice',
                                            'short_text' => 'Short text',
                                            'long_text' => 'Long text',
                                            'true_false' => 'True / False',
                                        ];
                                    @endphp

                                    {{ $typeLabels[$question->type] ?? $question->type }}
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($question->question_text), 100) }}
                                    </div>
                                    @if($question->helper_text)
                                        <div class="small text-muted">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($question->helper_text), 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div>+{{ number_format((float) $question->default_correct_points, 2, '.', '') }}</div>
                                    <div class="small text-muted">
                                        {{ number_format((float) ($question->default_incorrect_points ?? 0), 2, '.', '') }}
                                    </div>
                                </td>
                                <td>{{ $question->difficulty_level ?? '—' }}</td>
                                <td>{{ $question->is_active ? 'Так' : 'Ні' }}</td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <a href="{{ route('admin.testing.questions.edit', $question) }}"
                                           class="btn btn-sm btn-primary">
                                            Редагувати
                                        </a>

                                        <a href="{{ route('admin.testing.questions.options.index', $question) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            Варіанти
                                        </a>

                                        <form action="{{ route('admin.testing.questions.destroy', $question) }}"
                                              method="POST"
                                              onsubmit="return confirm('Видалити питання?')">
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
                                <td colspan="8" class="text-center text-muted py-4">
                                    Питань поки немає
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{ $questions->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
