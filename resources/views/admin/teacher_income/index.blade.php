@extends('admin.layouts.layout')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .app-main { padding-top: 1.25rem; }
        .page-title{ margin-bottom:1rem; font-weight:600; letter-spacing:.2px; }
        .page-subtitle{ margin-top:-.25rem; color:#6c757d; }
        table.dataTable > thead > tr > th{ background:#f8f9fa; border-bottom:1px solid #dee2e6!important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button{
            margin:0 .25rem!important; padding:.375rem .65rem!important; border-radius:.375rem!important;
            border:1px solid #dee2e6!important; background:#fff!important; cursor:pointer;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover{
            background:#0d6efd!important; color:#fff!important; border-color:#0d6efd!important;
        }
        .dataTables_info{ color:#6c757d; }
        .form-select,.btn{ border-radius:.375rem; }
    </style>
@endsection

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="page-title">Мої фінанси за {{ $selectedMonth }}/{{ $selectedYear }}</h2>
                    <div class="page-subtitle">Нарахування за індивідуальні та групові заняття</div>
                </div>
            </div>

            {{-- Форма вибору --}}
            <form method="GET" class="row g-3 mb-3">
                <div class="col-auto">
                    <select name="month" class="form-select">
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ $m == $selectedMonth ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="year" class="form-select">
                        @foreach(range(now()->year - 2, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Показати</button>
                </div>
            </form>

            {{-- Якщо даних нема — інфо-плашка над таблицею --}}
            @if(empty($data) || count($data) === 0)
                <div class="alert alert-info mb-2">Немає занять у цьому місяці.</div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle w-100" id="income-table">
                            <thead>
                            <tr>
                                <th>Учень/Група</th>
                                <th>Індивідуальні заняття</th>
                                <th>Групові заняття</th>
                                <th>З індивідуальних</th>
                                <th>З групових</th>
                                <th>Всього</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $row)
                                <tr>
                                    <td>{{ $row['student']->full_name ?? '—' }}</td>
                                    <td>{{ $row['individualCount'] }}</td>
                                    <td>{{ $row['groupCount'] }}</td>
                                    <td>{{ number_format($row['individualEarned'], 2) }} ₴</td>
                                    <td>{{ number_format($row['groupEarned'], 2) }} ₴</td>
                                    <td><strong>{{ number_format($row['totalEarned'], 2) }} ₴</strong></td>
                                </tr>
                            @endforeach
                            {{-- УВАГА: ніяких colspan-рядків у tbody --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            const sel = '#income-table';

            // Якщо таблиця вже ініціалізована — знищити (захист від дублю)
            if ($.fn.dataTable.isDataTable(sel)) {
                $(sel).DataTable().clear().destroy();
                $(sel).find('thead th, tbody td').removeAttr('style');
            }

            // Контроль відповідності кількості колонок
            const thCount = $(sel).find('thead th').length;
            let badRow = false;
            $(sel).find('tbody tr').each(function(){
                const c = $(this).children('td').length;
                if (c && c !== thCount){ badRow = true; }
            });
            if (badRow) {
                console.error('Column mismatch detected — перевір HTML рядків tbody.');
                return;
            }

            $(sel).DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json',
                    emptyTable: 'Немає занять у цьому місяці.'
                },
                pageLength: 10,
                lengthMenu: [5,10,25,50],
                order: [[5,'desc']],
                dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        });
    </script>
@endsection
