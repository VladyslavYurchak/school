@extends('admin.layouts.layout')

@section('styles')
    {{-- DataTables + Bootstrap 5 theme --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* Відступ зверху сторінки */
        .app-main { padding-top: 1.25rem; }

        /* Заголовок сторінки */
        .page-title {
            margin-bottom: 1rem;
            font-weight: 600;
            letter-spacing: .2px;
        }
        .page-subtitle {
            margin-top: -.25rem;
            color: #6c757d;
        }

        /* Таблиця */
        table.dataTable > thead > tr > th {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6 !important;
        }

        /* Пагінація DataTables — відступи та стан активної */
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

        /* Інфо-рядок */
        .dataTables_info { color: #6c757d; }

        /* Контроли зверху */
        .dataTables_length select { border-radius: .375rem; }
        .dataTables_filter input { border-radius: .375rem; }

        /* Бейджі статусів */
        .badge-soft { padding: .45em .6em; border-radius: .5rem; font-weight: 500; }
        .badge-soft-success { background: #e8f5e9; color: #198754; border: 1px solid #ccead0; }
        .badge-soft-secondary { background: #f1f3f5; color: #6c757d; border: 1px solid #e9ecef; }
    </style>
@endsection

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="page-title">Мої групи</h2>
                    <div class="page-subtitle">Перелік ваших груп із розкладом та статусом</div>
                </div>
                {{-- Жодних кнопок додавання: групи створює адміністратор --}}
            </div>

            @if(session('success'))
                <div class="alert alert-success mt-3">{{ session('success') }}</div>
            @endif

            @if($groups->isEmpty())
                <div class="alert alert-info mt-3">
                    Наразі у вас немає груп.
                </div>
            @else
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle w-100" id="groups-table">
                                <thead>
                                <tr>
                                    <th>Група</th>
                                    <th>Кількість студентів</th>
                                    <th>Розклад</th>
                                    <th>Статус</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($groups as $group)
                                    @php
                                        $studentsCount = $group->students_count ?? ($group->students->count() ?? 0);
                                        $isActive = $group->is_active ?? true;
                                    @endphp
                                    <tr>
                                        <td>{{ $group->name ?? '—' }}</td>
                                        <td>{{ $studentsCount }}</td>
                                        <td>
                                            {{-- Можна підставити ваш готовий accessor/поле --}}
                                            {{ $group->schedule_text ?? ($group->schedule ?? '—') }}
                                        </td>
                                        <td>
                                            @if($isActive)
                                                <span class="badge-soft badge-soft-success">Активна</span>
                                            @else
                                                <span class="badge-soft badge-soft-secondary">Архів</span>
                                            @endif
                                        </td>
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
            $('#groups-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']],
                dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        });
    </script>
@endsection
