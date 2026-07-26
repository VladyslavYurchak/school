@extends('admin.layouts.layout')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
    @php
        use Illuminate\Support\Carbon;
        use Illuminate\Support\Str;

        $showWeekView = $view === 'week';
        $dataTableOrder = $showWeekView
            ? [[0, 'asc'], [1, 'asc']]
            : [[0, 'asc']];
        $periodTitle = $showWeekView
            ? 'Заняття за тиждень: ' . $startOfWeek->translatedFormat('d.m.Y') . ' - ' . $endOfWeek->translatedFormat('d.m.Y')
            : 'Заняття за день: ' . Carbon::parse($date)->translatedFormat('d.m.Y');

        $statusConfig = [
            'completed' => ['label' => 'Проведено', 'class' => 'admin-badge-free'],
            'charged' => ['label' => 'Списано', 'class' => 'admin-badge-paid'],
            'rescheduled' => ['label' => 'Перенесено', 'class' => 'admin-badge-muted'],
        ];

        $typeConfig = [
            'individual' => 'Індивідуальне',
            'group' => 'Групове',
            'pair' => 'Парне',
            'trial' => 'Пробне',
        ];

        $formatDate = static fn ($value, string $format = 'd.m.Y (D)') => Carbon::parse($value)->translatedFormat($format);
        $formatTime = static fn ($value) => Carbon::parse($value)->format('H:i');

        $trialCount = $logs->where('lesson_type', 'trial')->count();
        $trialCosts = (float) $logs->where('lesson_type', 'trial')->sum('teacher_payout_amount');
        $totalPayout = (float) $logs->sum('teacher_payout_amount');
        $completedCnt = $logs->where('status', 'completed')->count();
        $chargedCnt = $logs->where('status', 'charged')->count();
    @endphp

    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-clipboard-data"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Інформація по заняттях</h1>
                        <p class="admin-subtitle">{{ $periodTitle }}</p>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Період</h2>
                </div>

                <div class="admin-panel-body">
                    <form method="GET" class="admin-filter-grid admin-filter-grid-three">
                        <div class="admin-field">
                            <label for="information-date">Дата</label>
                            <input id="information-date" type="date" name="date" value="{{ $date }}" class="form-control">
                        </div>

                        <div class="admin-field">
                            <label for="information-view">Вигляд</label>
                            <select id="information-view" name="view" class="form-select">
                                <option value="day" {{ $view === 'day' ? 'selected' : '' }}>За день</option>
                                <option value="week" {{ $view === 'week' ? 'selected' : '' }}>За тиждень</option>
                            </select>
                        </div>

                        <button class="admin-btn-primary">
                            <i class="bi bi-funnel"></i>
                            Показати
                        </button>
                    </form>
                </div>
            </section>

            <div class="admin-teacher-summary">
                <div class="admin-summary-card">
                    <span class="admin-summary-label">Пробні заняття</span>
                    <span class="admin-summary-value">{{ $trialCount }}</span>
                    <span class="admin-summary-label">Витрати: {{ number_format($trialCosts, 2, ',', ' ') }} грн</span>
                </div>
                <div class="admin-summary-card">
                    <span class="admin-summary-label">Виплати викладачам</span>
                    <span class="admin-summary-value">{{ number_format($totalPayout, 2, ',', ' ') }} грн</span>
                </div>
                <div class="admin-summary-card">
                    <span class="admin-summary-label">Статуси</span>
                    <span class="admin-summary-value">{{ $completedCnt + $chargedCnt }}</span>
                    <span class="admin-summary-label">Проведено: {{ $completedCnt }} / Списано: {{ $chargedCnt }}</span>
                </div>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Проведені та списані заняття</h2>
                    <span class="admin-badge admin-badge-muted">Записів: {{ $logs->count() }}</span>
                </div>

                <div class="admin-panel-body">
                    @if($logs->count())
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table-lg w-100" id="logs-table">
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
                                    <th>Виплата</th>
                                    <th>Нотатки</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($logs as $log)
                                    @php
                                        $statusValue = $log->status instanceof \BackedEnum ? $log->status->value : ($log->status ?? '');
                                        $typeValue = $log->lesson_type instanceof \BackedEnum ? $log->lesson_type->value : ($log->lesson_type ?? '');
                                        $statusBadge = $statusConfig[$statusValue] ?? null;
                                        $typeLabel = $typeConfig[$typeValue] ?? ($typeValue ? Str::ucfirst($typeValue) : '-');
                                    @endphp
                                    <tr>
                                        @if($showWeekView)
                                            <td data-order="{{ Carbon::parse($log->date)->format('Y-m-d') }}">{{ $formatDate($log->date) }}</td>
                                        @endif
                                        <td>{{ $formatTime($log->time) }}</td>
                                        <td><span class="admin-badge admin-badge-muted">{{ $typeLabel }}</span></td>
                                        <td>{{ $log->student?->full_name ?? '-' }}</td>
                                        <td>{{ $log->teacher?->full_name ?? '-' }}</td>
                                        <td>{{ $log->group?->name ?? '-' }}</td>
                                        <td>{{ $log->duration }} хв</td>
                                        <td>
                                            @if($statusBadge)
                                                <span class="admin-badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                                            @else
                                                <span class="admin-badge admin-badge-muted">{{ $statusValue ? Str::ucfirst($statusValue) : '-' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $log->teacher_payout_amount !== null ? number_format((float) $log->teacher_payout_amount, 2, ',', ' ') . ' грн' : '-' }}
                                        </td>
                                        <td class="admin-note-truncate" title="{{ $log->notes }}">
                                            {{ $log->notes ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <h3>Немає занять</h3>
                            <p>Для вибраного періоду немає проведених або списаних занять.</p>
                        </div>
                    @endif
                </div>
            </section>

            @if($rescheduledLessons->count())
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <h2 class="admin-panel-title">Перенесені заняття</h2>
                        <span class="admin-badge admin-badge-muted">Записів: {{ $rescheduledLessons->count() }}</span>
                    </div>

                    <div class="admin-panel-body">
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table w-100" id="rescheduled-table">
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
                                        $typeValue = $lesson->lesson_type instanceof \BackedEnum ? $lesson->lesson_type->value : ($lesson->lesson_type ?? '');
                                        $typeLabel = $typeConfig[$typeValue] ?? ($typeValue ? Str::ucfirst($typeValue) : '-');
                                    @endphp
                                    <tr>
                                        @if($showWeekView)
                                            <td data-order="{{ Carbon::parse($lesson->start_date)->format('Y-m-d') }}">{{ $formatDate($lesson->start_date) }}</td>
                                        @endif
                                        <td>{{ $formatTime($lesson->start_date) }}</td>
                                        <td><span class="admin-badge admin-badge-muted">{{ $typeLabel }}</span></td>
                                        <td>{{ $lesson->student?->full_name ?? '-' }}</td>
                                        <td>{{ $lesson->teacher?->full_name ?? '-' }}</td>
                                        <td>{{ $lesson->group?->name ?? '-' }}</td>
                                        <td>{{ $lesson->initiator ? Str::ucfirst($lesson->initiator) : '-' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
            const lang = { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json' };
            const dom =
                "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

            if ($('#logs-table').length) {
                $('#logs-table').DataTable({
                    language: lang,
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50],
                    order: @json($dataTableOrder),
                    dom
                });
            }

            if ($('#rescheduled-table').length) {
                $('#rescheduled-table').DataTable({
                    language: lang,
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50],
                    order: @json($dataTableOrder),
                    dom
                });
            }
        });
    </script>
@endsection
