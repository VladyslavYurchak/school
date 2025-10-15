@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <h2 class="mb-4">
            @if($view === 'week')
                Заняття за тиждень: {{ $startOfWeek->format('d.m.Y') }} – {{ $endOfWeek->format('d.m.Y') }}
            @else
                Заняття за день: {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
            @endif
        </h2>

        {{-- Форма вибору дати + типу перегляду --}}
        <form method="GET" class="mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                </div>
                <div class="col-auto">
                    <select name="view" class="form-select">
                        <option value="day" {{ $view==='day' ? 'selected' : '' }}>За день</option>
                        <option value="week" {{ $view==='week' ? 'selected' : '' }}>За тиждень</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Показати</button>
                </div>
            </div>
        </form>

        {{-- Таблиця проведених та списаних уроків --}}
        @if($logs->count())
            <table class="table table-striped table-hover shadow-sm rounded">
                <thead class="table-dark">
                <tr>
                    @if($view === 'week')
                        <th>Дата</th>
                    @endif
                    <th>Час</th>
                    <th>Студент</th>
                    <th>Викладач</th>
                    <th>Група</th>
                    <th>Тривалість</th>
                    <th>Статус</th>
                    <th>Нотатки</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logs as $log)
                    <tr>
                        @if($view === 'week')
                            <td>{{ \Carbon\Carbon::parse($log->date)->format('d.m.Y (D)') }}</td>
                        @endif
                        <td>{{ \Carbon\Carbon::parse($log->time)->format('H:i') }}</td>
                        <td>{{ $log->student?->full_name ?? '-' }}</td>
                        <td>{{ $log->teacher?->full_name ?? '-' }}</td>
                        <td>{{ $log->group?->name ?? '—' }}</td>
                        <td>{{ $log->duration }} хв</td>
                        <td>
                            @if($log->status === 'completed')
                                <span class="badge bg-success">Проведено</span>
                            @elseif($log->status === 'charged')
                                <span class="badge bg-danger">Списано</span>
                            @elseif($log->status === 'rescheduled')
                                <span class="badge bg-warning text-dark">Перенесено</span>
                            @endif
                        </td>
                        <td>{{ $log->notes ?? '' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="alert alert-info">Немає занять для вибраного періоду.</div>
        @endif

        {{-- Таблиця перенесених уроків --}}
        @if($rescheduledLessons->count())
            <h3 class="mt-5">Перенесені заняття</h3>
            <table class="table table-striped table-hover shadow-sm rounded">
                <thead class="table-dark">
                <tr>
                    @if($view === 'week')
                        <th>Дата</th>
                    @endif
                    <th>Час</th>
                    <th>Студент</th>
                    <th>Викладач</th>
                    <th>Група</th>
                    <th>Ініціатор</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rescheduledLessons as $lesson)
                    <tr>
                        @if($view === 'week')
                            <td>{{ \Carbon\Carbon::parse($lesson->start_date)->format('d.m.Y (D)') }}</td>
                        @endif
                        <td>{{ \Carbon\Carbon::parse($lesson->start_date)->format('H:i') }}</td>
                        <td>{{ $lesson->student?->full_name ?? '-' }}</td>
                        <td>{{ $lesson->teacher?->full_name ?? '-' }}</td>
                        <td>{{ $lesson->group?->name ?? '—' }}</td>
                        <td>{{ ucfirst($lesson->initiator ?? '-') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
