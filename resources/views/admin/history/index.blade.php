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

        $lessonTypeLabels = [
            'individual' => 'Індивідуальне',
            'group' => 'Групове',
            'pair' => 'Парне',
            'trial' => 'Пробне',
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
                                    <th>Учень / група</th>
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
                                        $meta = is_array($log->meta) ? $log->meta : [];
                                        $lessonType = $log->lesson?->lesson_type;
                                        $lessonType = $lessonType instanceof \BackedEnum ? $lessonType->value : $lessonType;
                                        $lessonType ??= $meta['lesson_type'] ?? null;
                                        $studentName = $log->lesson?->student?->full_name ?? ($meta['student_name'] ?? null);
                                        $groupName = $log->lesson?->group?->name ?? ($meta['group_name'] ?? null);
                                        $teacherName = $log->lesson?->teacher?->full_name ?? ($meta['teacher_name'] ?? null);
                                        $lessonTitle = $log->lesson?->title ?? ($meta['lesson_title'] ?? null);
                                        $studentId = $log->lesson?->student_id ?? ($meta['student_id'] ?? null);
                                        $groupId = $log->lesson?->group_id ?? ($meta['group_id'] ?? null);
                                        $teacherId = $log->lesson?->teacher_id ?? ($meta['teacher_id'] ?? null);
                                    @endphp
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>
                                            <span class="admin-badge {{ $action['class'] }}">{{ $action['label'] }}</span>
                                        </td>
                                        <td>{{ $log->lesson_datetime ? $log->lesson_datetime->format('d.m.Y H:i') : '-' }}</td>
                                        <td>{{ $log->new_lesson_datetime ? $log->new_lesson_datetime->format('d.m.Y H:i') : '-' }}</td>
                                        <td>
                                            <strong>#{{ $log->lesson_id }}</strong>
                                            <span class="admin-badge admin-badge-muted">
                                                {{ $lessonTypeLabels[$lessonType] ?? ($lessonType ?: 'Тип не вказано') }}
                                            </span>
                                            <br>
                                            <span class="text-muted">{{ $lessonTitle ?: 'Назву не вказано' }}</span>
                                        </td>
                                        <td>
                                            @if(in_array($lessonType, ['group', 'pair'], true))
                                                <strong>{{ $lessonType === 'pair' ? 'Пара' : 'Група' }}:</strong>
                                                {{ $groupName ?: ($groupId ? "#{$groupId} (видалена)" : 'не вказана') }}
                                            @elseif($lessonType === 'trial' && !$studentName)
                                                Пробне заняття без учня
                                            @else
                                                <strong>Учень:</strong>
                                                {{ $studentName ?: ($studentId ? "#{$studentId} (видалений)" : 'не вказаний') }}
                                            @endif
                                        </td>
                                        <td>{{ $teacherName ?: ($teacherId ? "#{$teacherId} (видалений)" : '-') }}</td>
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
