@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-person-check"></i>
                            Тестування
                        </span>
                        <h1 class="admin-title">Сесії тестування</h1>
                        <p class="admin-subtitle">Результати проходження безкоштовного тестування на сайті.</p>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список сесій</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $sessions->total() }}</span>
                </div>

                <div class="admin-panel-body">
                    @if($sessions->count())
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table-lg">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Мова</th>
                                    <th>Статус</th>
                                    <th>Бал</th>
                                    <th>Макс</th>
                                    <th>Рівень</th>
                                    <th>Початок</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($sessions as $session)
                                    <tr>
                                        <td>{{ $session->id }}</td>
                                        <td>{{ strtoupper($session->language_code) }}</td>
                                        <td>
                                            @if($session->status === 'completed' || $session->status === 'finished')
                                                <span class="admin-badge admin-badge-free">Завершено</span>
                                            @else
                                                <span class="admin-badge admin-badge-muted">В процесі</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $session->total_weighted_score }}</strong></td>
                                        <td>{{ $session->max_weighted_score }}</td>
                                        <td>
                                            @if($session->detected_level)
                                                <span class="admin-badge admin-badge-paid">{{ $session->detected_level }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $session->started_at ? $session->started_at->format('d.m.Y H:i') : '-' }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.testing.sessions.show', $session) }}" class="admin-btn-soft">
                                                <i class="bi bi-eye"></i>
                                                Переглянути
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $sessions->onEachSide(2)->links('admin.pagination.pagination') }}
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-person-check"></i>
                            </div>
                            <h3>Сесій поки немає</h3>
                            <p>Коли відвідувачі проходитимуть тестування, результати зʼявляться тут.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
