@extends('admin.layouts.layout')

@push('styles')
    {{-- Підключення таблиць та календарика --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="{{ asset('admin/students/payment.css') }}?v=1" rel="stylesheet">
@endpush

@section('styles')
    {{-- DataTables + Bootstrap 5 theme --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .app-main { padding-top: 1.25rem; }

        .page-title {
            margin-bottom: 1rem;
            font-weight: 600;
            letter-spacing: .2px;
        }

        /* Хедер таблиць */
        table.dataTable > thead > tr > th{
            background:#f8f9fa;
            border-bottom:1px solid #dee2e6 !important;
        }

        /* Пагінація DataTables — відступи/стани як скрізь */
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
    </style>
@endsection

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap">
                <h2 class="page-title mb-2">Учні</h2>

                <div class="d-flex flex-column align-items-end" style="max-width: 300px;">
                    <button class="btn btn-success mb-2" id="toggle-student-form">
                        + Додати учня
                    </button>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="studentSearch" class="form-control" placeholder="Пошук за ім'ям або прізвищем...">
                    </div>
                </div>
            </div>

            {{-- Повідомлення про успіх і помилки --}}
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Форма додавання учня --}}
            <div id="student-form-container" class="mb-4 d-none">
                @include('admin.students.add_student_form')
            </div>

            {{-- Активні студенти --}}
            <h4 class="mb-2">Активні студенти</h4>
            <div class="card shadow-sm mb-5">
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle w-100" id="active-students-table">
                        <thead>
                        <tr>
                            <th>Прізвище</th>
                            <th>Імʼя</th>
                            <th>Викладач</th>
                            <th>Абонемент</th>
                            <th>Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $nowKyiv = \Carbon\Carbon::now('Europe/Kyiv');
                            $currentMonth = $nowKyiv->format('Y-m');
                        @endphp

                        @foreach($activeStudents as $student)
                            @php
                                $isUnpaid = empty($paidMonthsByStudent[$student->id][$currentMonth] ?? null);
                                $paidMonths = $paidMonthsByStudent[$student->id] ?? [];
                            @endphp
                            <tr class="{{ $isUnpaid ? 'table-danger' : '' }}">
                                <td>{{ $student->last_name }}</td>
                                <td>{{ $student->first_name }}</td>
                                <td>{{ $student->teacher->full_name ?? '-' }}</td>
                                <td>
                                    @if($student->subscriptionTemplate)
                                        {{ $student->subscriptionTemplate->title }}
                                        ({{ $student->subscriptionTemplate->lessons_per_week }} р/т)
                                        ({{ $student->subscriptionTemplate->price }} грн)
                                    @else
                                        {{ $singlePaymentsCount[$student->id] ?? 0 }} поразових оплат
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#studentModal{{ $student->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $student->id }}" title="Оплата">
                                        💰
                                    </button>

                                    <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Видалити цього учня?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            @include('admin.students.partials.student_modal', ['student' => $student])
                            @php
                                $paidMonths = $paidMonthsByStudent[$student->id] ?? [];
                            @endphp
                            @include('admin.students.partials.payment_modal', ['student' => $student, 'paidMonths' => $paidMonths])
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Неактивні студенти --}}
            <h4 class="mb-2">Неактивні студенти</h4>
            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle w-100" id="inactive-students-table">
                        <thead>
                        <tr>
                            <th>Прізвище</th>
                            <th>Імʼя</th>
                            <th>Викладач</th>
                            <th>Абонемент</th>
                            <th>Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($inactiveStudents as $student)
                            <tr>
                                <td>{{ $student->last_name }}</td>
                                <td>{{ $student->first_name }}</td>
                                <td>{{ $student->teacher->full_name ?? '-' }}</td>
                                <td>
                                    @if($student->subscriptionTemplate)
                                        {{ $student->subscriptionTemplate->title }}
                                        ({{ $student->subscriptionTemplate->lessons_per_week }} р/т)
                                        ({{ $student->subscriptionTemplate->price }}грн)
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#studentModal{{ $student->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Видалити цього учня?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @include('admin.students.partials.student_modal', ['student' => $student])
                        @endforeach
                        </tbody>
                    </table>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Показ/приховати форму
            document.getElementById('toggle-student-form')?.addEventListener('click', function () {
                document.getElementById('student-form-container')?.classList.toggle('d-none');
            });

            // DataTables ініціалізація
            const lang = { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json' };
            const dom  =
                "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

            const activeDT   = $('#active-students-table').length ? $('#active-students-table').DataTable({
                language: lang, pageLength: 10, lengthMenu: [5,10,25,50], order: [[0,'asc']], dom
            }) : null;

            const inactiveDT = $('#inactive-students-table').length ? $('#inactive-students-table').DataTable({
                language: lang, pageLength: 10, lengthMenu: [5,10,25,50], order: [[0,'asc']], dom
            }) : null;

            // Пошук: якщо є DataTables — делегуємо їм; інакше — старий мануальний фільтр
            const searchInput = document.getElementById('studentSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    const val = this.value;
                    if (activeDT || inactiveDT) {
                        if (activeDT)   activeDT.search(val).draw();
                        if (inactiveDT) inactiveDT.search(val).draw();
                    } else {
                        // Фолбек: мануальний фільтр
                        document.querySelectorAll('.card-body table tbody tr').forEach(row => {
                            const name = (row.cells[1]?.textContent || '').toLowerCase();
                            const surname = (row.cells[0]?.textContent || '').toLowerCase();
                            const v = val.toLowerCase();
                            row.style.display = (name.includes(v) || surname.includes(v)) ? '' : 'none';
                        });
                    }
                });
            }
        });
    </script>

    <script>
        window.activeStudentIds = @json($activeStudents->pluck('id'));
    </script>
    <script src="{{ asset('admin/students/payment.js') }}"></script>
@endsection
