@extends('admin.layouts.layout')

@section('title', 'Історія дій')

@section('content')
    @php
        $actionConfig = [
            'created' => ['label' => 'Створено', 'class' => 'admin-badge-free'],
            'rescheduled' => ['label' => 'Перенесено', 'class' => 'admin-badge-muted'],
            'cancelled' => ['label' => 'Скасовано', 'class' => 'admin-badge-paid'],
            'completed' => ['label' => 'Проведено', 'class' => 'admin-badge-free'],
        ];
    @endphp

    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-clock-history"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Історія дій</h1>
                        <p class="admin-subtitle">Журнал створення, перенесення, скасування та завершення занять.</p>
                    </div>

                    <div class="admin-actions">
                        <span class="admin-badge admin-badge-muted">Записів: {{ $logs->total() }}</span>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Фільтр</h2>
                </div>

                <div class="admin-panel-body">
                    <form method="GET" action="{{ route('admin.history_actions.index') }}" class="admin-filter-grid">
                        <div class="admin-field">
                            <label for="teacher_id">Викладач</label>
                            <select name="teacher_id" id="teacher_id" class="form-select">
                                <option value="">Усі викладачі</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ (string) $teacherId === (string) $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->last_name }} {{ $teacher->first_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button class="admin-btn-primary" type="submit">
                            <i class="bi bi-funnel"></i>
                            Фільтрувати
                        </button>
                    </form>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Журнал дій</h2>
                </div>

                <div class="admin-panel-body">
                    @if($logs->count())
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table-lg">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дія</th>
                                    <th>Стара дата</th>
                                    <th>Нова дата</th>
                                    <th>Урок</th>
                                    <th>Викладач</th>
                                    <th>Хто виконав</th>
                                    <th>Записано</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($logs as $log)
                                    @php
                                        $action = $actionConfig[$log->action] ?? [
                                            'label' => $log->action,
                                            'class' => 'admin-badge-muted',
                                        ];
                                    @endphp
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>
                                            <span class="admin-badge {{ $action['class'] }}">{{ $action['label'] }}</span>
                                        </td>
                                        <td>{{ $log->lesson_datetime ? $log->lesson_datetime->format('d.m.Y H:i') : '-' }}</td>
                                        <td>{{ $log->new_lesson_datetime ? $log->new_lesson_datetime->format('d.m.Y H:i') : '-' }}</td>
                                        <td>
                                            @if($log->lesson)
                                                <strong>#{{ $log->lesson->id }}</strong><br>
                                                <span class="text-muted">{{ $log->lesson->title ?? '-' }}</span>
                                            @else
                                                <span class="text-muted">Урок видалено</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->lesson && $log->lesson->teacher)
                                                {{ $log->lesson->teacher->last_name }} {{ $log->lesson->teacher->first_name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $log->user?->name ?? 'system' }}</td>
                                        <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $logs->onEachSide(2)->links('admin.pagination.pagination') }}
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h3>Записів поки немає</h3>
                            <p>Коли уроки будуть створені, перенесені, скасовані або проведені, дії зʼявляться тут.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
