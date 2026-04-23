@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h1 class="mb-0">Діапазони результатів: {{ $test->title }}</h1>
                    <div class="text-muted small">Мова: {{ strtoupper($test->language_code) }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.testing.tests.index') }}" class="btn btn-outline-secondary">
                        До тестів
                    </a>

                    <a href="{{ route('admin.testing.tests.result-ranges.create', $test) }}" class="btn btn-custom">
                        Додати діапазон
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="alert alert-light border mb-3">
                        Ці діапазони використовуються для текстового опису підсумкового результату за шкалою
                        <strong>0–100</strong>. Основний рівень CEFR визначається окремо — за складністю питань.
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Назва</th>
                                <th>Рівень CEFR</th>
                                <th>Діапазон балів</th>
                                <th>Опис</th>
                                <th>Рекомендація</th>
                                <th class="text-end">Дії</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($ranges as $range)
                                <tr>
                                    <td>{{ $range->id }}</td>

                                    <td class="fw-semibold">
                                        {{ $range->title }}
                                    </td>

                                    <td>
                                        {{ $range->level_code ?: '—' }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $range->min_score, 2, '.', '') }}
                                        —
                                        {{ number_format((float) $range->max_score, 2, '.', '') }}
                                    </td>

                                    <td>
                                        @if($range->description)
                                            {{ \Illuminate\Support\Str::limit(strip_tags($range->description), 100) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($range->recommendation_text)
                                            {{ \Illuminate\Support\Str::limit(strip_tags($range->recommendation_text), 100) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a href="{{ route('admin.testing.result-ranges.edit', $range) }}"
                                               class="btn btn-sm btn-primary">
                                                Редагувати
                                            </a>

                                            <form action="{{ route('admin.testing.result-ranges.destroy', $range) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Видалити діапазон результатів?')">
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
                                        Діапазонів поки немає
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $ranges->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
