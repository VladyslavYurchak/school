@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h1 class="mb-0">Варіанти відповіді</h1>
                    <div class="text-muted small">
                        Питання #{{ $question->id }}
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.testing.tests.questions.index', $question->test) }}"
                       class="admin-btn-soft">
                        До питань
                    </a>

                    <a href="{{ route('admin.testing.questions.options.create', $question) }}"
                       class="admin-btn-primary">
                        Додати варіант
                    </a>
                </div>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-body">
                    <div class="fw-semibold mb-2">Текст питання:</div>
                    <div>{!! nl2br(e($question->question_text)) !!}</div>

                    @if($question->type)
                        <div class="mt-2 small text-muted">
                            Тип: {{ $question->type }} |
                            За правильну: {{ $question->default_correct_points }} |
                            За неправильну: {{ $question->default_incorrect_points }}                        </div>
                    @endif
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-body">
                    <div class="admin-table-wrap">
                    <table class="table admin-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Порядок</th>
                            <th>Варіант</th>
                            <th>Правильний</th>
                            <th>Бали</th>
                            <th>Пояснення</th>
                            <th class="text-end">Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($options as $option)
                            <tr>
                                <td>{{ $option->id }}</td>
                                <td>{{ $option->sort_order }}</td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($option->option_text), 120) }}
                                    </div>

                                    @if($option->option_value)
                                        <div class="small text-muted">
                                            Value: {{ $option->option_value }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($option->is_correct)
                                        <span class="badge text-bg-success">Так</span>
                                    @else
                                        <span class="badge text-bg-secondary">Ні</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!is_null($option->points))
                                        <span class="fw-semibold">{{ $option->points }}</span>
                                    @else
                                        <span class="text-muted">
                                                {{ $option->is_correct
                                                ? $question->default_correct_points
                                                : $question->default_incorrect_points }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($option->explanation)
                                        {{ \Illuminate\Support\Str::limit(strip_tags($option->explanation), 80) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="admin-row-actions">
                                        <a href="{{ route('admin.testing.options.edit', $option) }}"
                                           class="admin-btn-warning">
                                            Редагувати
                                        </a>

                                        <form action="{{ route('admin.testing.options.destroy', $option) }}"
                                              method="POST"
                                              onsubmit="return confirm('Видалити варіант відповіді?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn-danger">
                                                Видалити
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Варіантів відповіді поки немає
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>

                    {{ $options->onEachSide(2)->links('admin.pagination.pagination') }}
                </div>
            </section>

        </div>
    </div>
@endsection
