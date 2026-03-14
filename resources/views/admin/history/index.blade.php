@extends('admin.layouts.layout')

@section('title', 'Історія дій')

@section('css')
<style>
        /* робимо текст у таблиці нормальним чорним */
        .history-table,
        .history-table th,
        .history-table td {
            color: #000 !important;
            background-color: #fff !important;
        }

        /* бейджі саме для історії */
        .badge-history {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }

        .badge-history-created {
            background-color: #007bff; /* синій */
            color: #ffffff !important;
        }

        .badge-history-rescheduled {
            background-color: #ffc107; /* жовтий */
            color: #212529 !important;
        }

        .badge-history-cancelled {
            background-color: #dc3545; /* червоний */
            color: #ffffff !important;
        }

        .badge-history-completed {
            background-color: #28a745; /* зелений */
            color: #ffffff !important;
        }
    </style>
@endsection

@section('content_header')
    <h1 class="mb-3">Історія дій з уроками</h1>
@stop

@section('content')

    <div class="card shadow-sm">

        {{-- ФІЛЬТРИ --}}
        <div class="card-header bg-white border-bottom">
            <form method="GET" action="{{ route('admin.history_actions.index') }}" class="form-inline">

                <div class="form-group mr-3">
                    <label for="teacher_id" class="mr-2 mb-0">Викладач:</label>
                    <select name="teacher_id" id="teacher_id" class="form-control">
                        <option value="">— Всі викладачі —</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}"
                                {{ (string)$teacherId === (string)$t->id ? 'selected' : '' }}>
                                {{ $t->last_name }} {{ $t->first_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-primary">
                    <i class="fa fa-filter"></i> Фільтрувати
                </button>

            </form>
        </div>

        {{-- ТАБЛИЦЯ --}}
        <div class="card-body p-0">

            <table class="table table-striped table-hover table-bordered mb-0 history-table">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;">ID</th>
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

                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>

                        {{-- ДІЯ з примусовими кольорами --}}
                        <td>
                            @switch($log->action)
                                @case('created')
                                    <span class="badge-history badge-history-created">Створено</span>
                                    @break

                                @case('rescheduled')
                                    <span class="badge-history badge-history-rescheduled">Перенесено</span>
                                    @break

                                @case('cancelled')
                                    <span class="badge-history badge-history-cancelled">Скасовано</span>
                                    @break

                                @case('completed')
                                    <span class="badge-history badge-history-completed">Проведено</span>
                                    @break

                                @default
                                    <span class="badge-history" style="background-color:#6c757d; color:#fff !important;">
                                            {{ $log->action }}
                                        </span>
                            @endswitch
                        </td>

                        <td>
                            {{ $log->lesson_datetime
                                ? $log->lesson_datetime->format('d.m.Y H:i')
                                : '—' }}
                        </td>

                        <td>
                            {{ $log->new_lesson_datetime
                                ? $log->new_lesson_datetime->format('d.m.Y H:i')
                                : '—' }}
                        </td>

                        <td>
                            @if($log->lesson)
                                <strong>#{{ $log->lesson->id }}</strong><br>
                                <span class="text-muted">{{ $log->lesson->title ?? '—' }}</span>
                            @else
                                <em class="text-muted">урок видалено</em>
                            @endif
                        </td>

                        <td>
                            @if($log->lesson && $log->lesson->teacher)
                                {{ $log->lesson->teacher->last_name }}
                                {{ $log->lesson->teacher->first_name }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if($log->user)
                                {{ $log->user->name }}
                            @else
                                <em class="text-muted">system</em>
                            @endif
                        </td>

                        <td>
                            {{ $log->created_at->format('d.m.Y H:i') }}
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted p-3">
                            Немає записів
                        </td>
                    </tr>
                @endforelse

                </tbody>

            </table>

        </div>

        <div class="card-footer bg-white">
            {{ $logs->links() }}
        </div>

    </div>

@stop
