@extends('admin.layouts.layout')

@section('styles')
    {{-- DataTables + Bootstrap 5 theme --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* Відступ зверху сторінки, якщо хедер фіксований */
        .app-main { padding-top: 1.25rem; }

        /* Уніфікований вигляд карток/заголовків на сторінках викладача */
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

        /* Пагінація DataTables — відступи та клікабельність */
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

        /* “Показано від …” — дрібніший та спокійний колір */
        .dataTables_info { color: #6c757d; }

        /* Поле пошуку та селект кількості на сторінці — вирівнювання */
        .dataTables_length select { border-radius: .375rem; }
        .dataTables_filter input { border-radius: .375rem; }
    </style>
@endsection

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="page-title">Мої студенти</h2>
                    <div class="page-subtitle">Список активних студентів та їхні контакти</div>
                </div>
                {{-- <a href="{{ route('admin.students.create') }}" class="btn btn-primary">+ Додати студента</a> --}}
            </div>

            @if(session('success'))
                <div class="alert alert-success mt-3">{{ session('success') }}</div>
            @endif

            @if($students->isEmpty())
                <div class="alert alert-info mt-3">
                    У вас немає активних студентів.
                </div>
            @else
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle w-100" id="students-table">
                                <thead>
                                <tr>
                                    <th>Ім'я</th>
                                    <th>Прізвище</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($students as $student)
                                    <tr>
                                        <td>{{ $student->first_name }}</td>
                                        <td>{{ $student->last_name }}</td>
                                        <td>
                                            <a href="mailto:{{ $student->email }}" class="text-decoration-none">
                                                {{ $student->email }}
                                            </a>
                                        </td>
                                        <td>{{ $student->phone ?? '-' }}</td>
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
    {{-- jQuery вже є в layout, але якщо треба — залишаю на випадок відсутності:
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
            $('#students-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[1, 'asc']], // Прізвище за замовчуванням
                // Увімкнути BS5 компонування контролів
                dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        });
    </script>
@endsection
