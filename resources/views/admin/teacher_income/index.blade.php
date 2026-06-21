@extends('admin.layouts.layout')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
    @php
        $totalIndividual = collect($data)->sum('individualCount');
        $totalGroup = collect($data)->sum('groupCount');
        $totalEarned = collect($data)->sum('totalEarned');
    @endphp

    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-cash-coin"></i>
                            Викладач
                        </span>
                        <h1 class="admin-title">Мої розрахунки</h1>
                        <p class="admin-subtitle">Нарахування за проведені індивідуальні, групові та парні заняття.</p>
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
                            <label for="teacher-income-month">Місяць</label>
                            <select id="teacher-income-month" name="month" class="form-select">
                                @foreach(range(1,12) as $m)
                                    <option value="{{ $m }}" {{ $m == $selectedMonth ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="admin-field">
                            <label for="teacher-income-year">Рік</label>
                            <select id="teacher-income-year" name="year" class="form-select">
                                @foreach(range(now()->year - 2, now()->year + 1) as $y)
                                    <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-funnel"></i>
                            Показати
                        </button>
                    </form>
                </div>
            </section>

            <div class="admin-teacher-summary">
                <div class="admin-summary-card">
                    <span class="admin-summary-label">Індивідуальних</span>
                    <span class="admin-summary-value">{{ $totalIndividual }}</span>
                </div>
                <div class="admin-summary-card">
                    <span class="admin-summary-label">Групових/парних</span>
                    <span class="admin-summary-value">{{ $totalGroup }}</span>
                </div>
                <div class="admin-summary-card">
                    <span class="admin-summary-label">До виплати</span>
                    <span class="admin-summary-value">{{ number_format($totalEarned, 2) }} грн</span>
                </div>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Деталі</h2>
                    <span class="admin-badge admin-badge-muted">{{ $selectedMonth }}/{{ $selectedYear }}</span>
                </div>

                <div class="admin-panel-body">
                    @if(empty($data) || count($data) === 0)
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <h3>Немає занять у цьому місяці</h3>
                            <p>Коли заняття будуть проведені або списані, вони зʼявляться тут.</p>
                        </div>
                    @else
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table-lg w-100" id="income-table">
                                <thead>
                                <tr>
                                    <th>Учень/група</th>
                                    <th>Індивідуальні</th>
                                    <th>Групові/парні</th>
                                    <th>З індивідуальних</th>
                                    <th>З групових</th>
                                    <th>Всього</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($data as $row)
                                    <tr>
                                        <td>{{ $row['student']->full_name ?? '-' }}</td>
                                        <td>{{ $row['individualCount'] }}</td>
                                        <td>{{ $row['groupCount'] }}</td>
                                        <td>{{ number_format($row['individualEarned'], 2) }} грн</td>
                                        <td>{{ number_format($row['groupEarned'], 2) }} грн</td>
                                        <td><strong>{{ number_format($row['totalEarned'], 2) }} грн</strong></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#income-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json',
                    emptyTable: 'Немає занять у цьому місяці.'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[5, 'desc']],
                dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        });
    </script>
@endsection
