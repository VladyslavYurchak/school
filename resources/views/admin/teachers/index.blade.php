@extends('admin.layouts.layout')

@section('styles')
    {{-- DataTables + Bootstrap 5 theme --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .app-main { padding-top: 1.25rem; }

        .page-title{
            margin-bottom: 1rem;
            font-weight: 600;
            letter-spacing: .2px;
        }

        table.dataTable > thead > tr > th{
            background:#f8f9fa;
            border-bottom:1px solid #dee2e6 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button{
            margin:0 .25rem !important;
            padding:.375rem .65rem !important;
            border-radius:.375rem !important;
            border:1px solid #dee2e6 !important;
            background:#fff !important;
            cursor:pointer;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover{
            background:#0d6efd !important;
            color:#fff !important;
            border-color:#0d6efd !important;
        }

        .dataTables_info{ color:#6c757d; }
        .dataTables_length select{ border-radius:.375rem; }
        .dataTables_filter input{ border-radius:.375rem; }

        .note-trunc{
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endsection

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="page-title mb-0">Викладачі</h2>
                <a href="{{ route('admin.teachers.create') }}" class="btn btn-success">+ Додати викладача</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle w-100" id="teachers-table">
                            <thead>
                            <tr>
                                <th>Прізвище</th>
                                <th>Ім’я</th>
                                <th>Телефон</th>
                                <th>Email</th>
                                <th>Ціна заняття</th>
                                <th>Активний</th>
                                <th>Нотатки</th>
                                <th class="text-nowrap">Дії</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($teachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->last_name }}</td>
                                    <td>{{ $teacher->first_name }}</td>
                                    <td>{{ $teacher->phone ?? '-' }}</td>
                                    <td>{{ $teacher->user->email ?? '-' }}</td>
                                    <td>{{ $teacher->lesson_price ? number_format($teacher->lesson_price, 2) . ' ₴' : '-' }}</td>
                                    <td>
                                        @if($teacher->is_active)
                                            <span class="badge bg-success">Так</span>
                                        @else
                                            <span class="badge bg-secondary">Ні</span>
                                        @endif
                                    </td>
                                    <td class="note-trunc" title="{{ $teacher->note }}">{{ $teacher->note }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.teachers.edit', $teacher->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.teachers.destroy', $teacher->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Видалити цього викладача?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div> {{-- /.table-responsive --}}
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    {{-- jQuery за потреби:
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#teachers-table').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json' },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']], // сортування за прізвищем
                dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        });
    </script>
@endsection
