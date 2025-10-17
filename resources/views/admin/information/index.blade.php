@extends('admin.layouts.layout')

@section('styles')
    {{-- DataTables + Bootstrap 5 theme --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* Як і всюди: відступ зверху під фіксований хедер */
        .app-main { padding-top: 1.25rem; }

        /* Заголовки сторінки */
        .page-title {
            margin-bottom: 1rem;
            font-weight: 600;
            letter-spacing: .2px;
        }
        .page-subtitle {
            margin-top: -.25rem;
            color: #6c757d;
        }

        /* Таблиці */
        table.dataTable > thead > tr > th {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6 !important;
        }

        /* Пагінація DataTables — такі ж відступи/стани */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            margin: 0 .25rem !important;
            padding: .375rem .65rem !important;
            border-radius: .375rem !important;
            border: 1px solid #dee2e6 !important;
            background: #fff !important;
            cursor: pointer;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #0d6efd !important;
            color: #fff !important;
            border-color: #0d6efd !important;
        }

        /* Інфо-рядок “Показано від …” */
        .dataTables_info { color: #6c757d; }

        /* Контроли зверху */
        .dataTables_length select { border-radius: .375rem; }
        .dataTables_filter input { border-radius: .375rem; }
    </style>
@endsection

@section('content')
    @php
        use Illuminate\Support\Carbon;
        use Illuminate\Support\Str;

        $showWeekView = $view === 'week';
        $periodTitle = $showWeekView
            ? 'Заняття за тиждень: ' . $startOfWeek->translatedFormat('d.m.Y') . ' – ' . $endOfWeek->translatedFormat('d.m.Y')
            : 'Заняття за день: ' . Carbon::parse($date)->translatedFormat('d.m.Y');

        $statusConfig = [
            'completed'   => ['label' => 'Проведено', 'class' => 'bg-success'],
            'charged'     => ['label' => 'Списано',   'class' => 'bg-danger'],
            'rescheduled' => ['label' => 'Перенесено','class' => 'bg-warning text-dark'],
        ];

        $typeConfig = [
            'individual' => ['label' => 'Індивідуальне', 'class' => 'bg-primary'],
            'group'      => ['label' => 'Групове',       'class' => 'bg-info text-dark'],
            'pair'       => ['label' => 'Парне',         'class' => 'bg-secondary'],
            'trial'      => ['label' => 'Пробне',        'class' => 'bg-warning text-dark'],
        ];

        $formatDate = static fn ($value, string $format = 'd.m.Y (D)') => Carbon::parse($value)->translatedFormat($format);
        $formatTime = static fn ($value) => Carbon::parse($value)->format('H:i');

        // агрегати за вибраний період
        $trialCount   = $logs->where('lesson_type', 'trial')->count();
        $trialCosts   = (float) $logs->where('lesson_type', 'trial')->sum('teacher_payout_amount');
        $totalPayout  = (float) $logs->sum('teacher_payout_amount');
        $completedCnt = $logs->where('status','completed')->count();
        $chargedCnt   = $logs->where('status','charged')->count();
    @endphp

    <main class="app-main">
        <div class="container-fluid">
            <h2 class="page-title">{{ $periodTitle }}</h2>

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

            {{-- Підсумки за період --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">Пробні заняття</div>
                            <div class="h5 mb-0">{{ $trialCount }}</div>
                            <small class="text-muted">Витрати: {{ number_format($trialCosts, 2, ',', ' ') }} грн</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">Всього виплат викладачам</div>
                            <div class="h5 mb-0">{{ number_format($totalPayout, 2, ',', ' ') }} грн</div>
                            <small class="text-muted">За вибраний період</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">Статуси</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-success">Проведено: {{ $completedCnt }}</span>
                                <span class="badge bg-danger">Списано: {{ $chargedCnt }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Таблиця проведених та списаних уроків --}}
            @if($logs->count())
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle w-100" id="logs-table">
                                <thead>
                                <tr>
                                    @if($showWeekView)
                                        <th>Дата</th>
                                    @endif
                                    <th>Час</th>
                                    <th>Тип</th>
                                    <th>Студент</th>
                                    <th>Викладач</th>
                                    <th>Група</th>
                                    <th>Тривалість</th>
                                    <th>Статус</th>
                                    <th>Виплата викладачу</th>
                                    <th>Нотатки</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($logs as $log)
                                    @php
                                        $statusBadge = $statusConfig[$log->status] ?? null;
                                        $typeBadge   = $typeConfig[$log->lesson_type] ?? null;
                                    @endphp
                                    <tr>
                                        @if($showWeekView)
                                            <td>{{ $formatDate($log->date) }}</td>
                                        @endif

                                        <td>{{ $formatTime($log->time) }}</td>

                                        <td>
                                            @if($typeBadge)
                                                <span class="badge {{ $typeBadge['class'] }}">{{ $typeBadge['label'] }}</span>
                                            @elseif($log->lesson_type)
                                                <span class="badge bg-secondary">{{ Str::ucfirst($log->lesson_type) }}</span>
                                            @else
                                                <span class="badge bg-secondary">—</span>
                                            @endif
                                        </td>

                                        <td>{{ $log->student?->full_name ?? '—' }}</td>
                                        <td>{{ $log->teacher?->full_name ?? '—' }}</td>
                                        <td>{{ $log->group?->name ?? '—' }}</td>
                                        <td>{{ $log->duration }} хв</td>

                                        <td>
                                            @if($statusBadge)
                                                <span class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                                            @elseif($log->status)
                                                <span class="badge bg-secondary">{{ Str::ucfirst($log->status) }}</span>
                                            @else
                                                <span class="badge bg-secondary">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            @php $payout = $log->teacher_payout_amount; @endphp
                                            {{ $payout !== null ? number_format((float)$payout, 2, ',', ' ') . ' грн' : '—' }}
                                        </td>

                                        <td>
                                            @if($log->notes)
                                                {!! nl2br(e($log->notes)) !!}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div> {{-- /.table-responsive --}}
                    </div>
                </div>
            @else
                <div class="alert alert-info">Немає занять для вибраного періоду.</div>
            @endif

            {{-- Таблиця перенесених уроків --}}
            @if($rescheduledLessons->count())
                <h3 class="mt-5">Перенесені заняття</h3>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle w-100" id="rescheduled-table">
                                <thead>
                                <tr>
                                    @if($showWeekView)
                                        <th>Дата</th>
                                    @endif
                                    <th>Час</th>
                                    <th>Тип</th>
                                    <th>Студент</th>
                                    <th>Викладач</th>
                                    <th>Група</th>
                                    <th>Ініціатор</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($rescheduledLessons as $lesson)
                                    @php
                                        $typeBadge = $typeConfig[$lesson->lesson_type ?? ''] ?? null;
                                    @endphp
                                    <tr>
                                        @if($showWeekView)
                                            <td>{{ $formatDate($lesson->start_date) }}</td>
                                        @endif
                                        <td>{{ $formatTime($lesson->start_date) }}</td>
                                        <td>
                                            @if($typeBadge)
                                                <span class="badge {{ $typeBadge['class'] }}">{{ $typeBadge['label'] }}</span>
                                            @else
                                                <span class="badge bg-secondary">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $lesson->student?->full_name ?? '—' }}</td>
                                        <td>{{ $lesson->teacher?->full_name ?? '—' }}</td>
                                        <td>{{ $lesson->group?->name ?? '—' }}</td>
                                        <td>{{ Str::ucfirst($lesson->initiator ?? '—') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div> {{-- /.table-responsive --}}
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection

@section('scripts')
    {{-- jQuery за потреби:
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
            const lang = { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json' };
            const dom  =
                "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

            if ($('#logs-table').length) {
                $('#logs-table').DataTable({
                    language: lang,
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50],
                    order: [[0, 'asc']], // перша видима колонка (Дата або Час)
                    dom
                });
            }

            if ($('#rescheduled-table').length) {
                $('#rescheduled-table').DataTable({
                    language: lang,
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50],
                    order: [[0, 'asc']], // перша видима колонка (Дата або Час)
                    dom
                });
            }
        });
    </script>
@endsection
