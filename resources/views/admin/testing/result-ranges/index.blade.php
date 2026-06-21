@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h1 class="mb-0">Діапазони результатів: {{ $test->title }}</h1>
                    <div class="text-muted small">Мова: {{ strtoupper($test->language_code) }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.testing.tests.index') }}" class="admin-btn-soft">
                        До тестів
                    </a>

                    <a href="{{ route('admin.testing.tests.result-ranges.create', $test) }}" class="admin-btn-primary">
                        Додати діапазон
                    </a>
                </div>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-body">
                    <div class="alert alert-light border mb-3">
                        Ці діапазони використовуються для текстового опису підсумкового результату за шкалою
                        <strong>0–100</strong>. Основний рівень CEFR визначається окремо — за складністю питань.
                    </div>

                    <div class="admin-table-wrap">
                        <table class="table admin-table">
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
                                        <div class="admin-row-actions">
                                            <a href="{{ route('admin.testing.result-ranges.edit', $range) }}"
                                               class="admin-btn-warning">
                                                Редагувати
                                            </a>

                                            <form action="{{ route('admin.testing.result-ranges.destroy', $range) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Видалити діапазон результатів?')">
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
                                        Діапазонів поки немає
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $ranges->onEachSide(2)->links('admin.pagination.pagination') }}
                </div>
            </section>

        </div>
    </div>
@endsection
