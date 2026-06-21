@extends('admin.layouts.layout')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-mortarboard"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Учні</h1>
                        <p class="admin-subtitle">Активні й неактивні учні, кабінети, викладачі, абонементи та оплати.</p>
                    </div>

                    <div class="admin-actions">
                        <button class="admin-btn-primary" id="toggle-student-form" type="button">
                            <i class="bi bi-plus-lg"></i>
                            Додати учня
                        </button>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <section id="student-form-container" class="admin-panel d-none">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Новий учень</h2>
                </div>
                <div class="admin-panel-body">
                    @include('admin.students.add_student_form')
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Пошук</h2>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-field">
                        <label for="studentSearch">Пошук за імʼям або прізвищем</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="studentSearch" class="form-control" placeholder="Почніть вводити імʼя або прізвище">
                        </div>
                    </div>
                </div>
            </section>

            @php
                $nowKyiv = \Carbon\Carbon::now('Europe/Kyiv');
                $currentMonth = $nowKyiv->format('Y-m');
            @endphp

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Активні студенти</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $activeStudents->count() }}</span>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-table-wrap">
                        <table class="table admin-table admin-teacher-table-lg w-100" id="active-students-table">
                            <thead>
                            <tr>
                                <th>Прізвище</th>
                                <th>Імʼя</th>
                                <th>Викладач</th>
                                <th>Абонемент</th>
                                <th class="text-end">Дії</th>
                            </tr>
                            </thead>
                            <tbody>
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
                                            {{ $singlePaymentsCount[$student->id] ?? 0 }} разових оплат
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="admin-compact-actions">
                                            <button class="admin-btn-soft" type="button" data-bs-toggle="modal" data-bs-target="#studentModal{{ $student->id }}" title="Переглянути">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="admin-btn-soft" type="button" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $student->id }}" title="Оплата">
                                                <i class="bi bi-cash-coin"></i>
                                            </button>
                                            <a href="{{ route('admin.students.edit', $student->id) }}" class="admin-btn-warning" title="Редагувати">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="admin-btn-danger" onclick="return confirm('Видалити цього учня?')" title="Видалити">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                @include('admin.students.partials.student_modal', ['student' => $student])
                                @include('admin.students.partials.payment_modal', ['student' => $student, 'paidMonths' => $paidMonths])
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Неактивні студенти</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $inactiveStudents->count() }}</span>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-table-wrap">
                        <table class="table admin-table admin-teacher-table-lg w-100" id="inactive-students-table">
                            <thead>
                            <tr>
                                <th>Прізвище</th>
                                <th>Імʼя</th>
                                <th>Викладач</th>
                                <th>Абонемент</th>
                                <th class="text-end">Дії</th>
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
                                            ({{ $student->subscriptionTemplate->price }} грн)
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="admin-compact-actions">
                                            <button class="admin-btn-soft" type="button" data-bs-toggle="modal" data-bs-target="#studentModal{{ $student->id }}" title="Переглянути">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="{{ route('admin.students.edit', $student->id) }}" class="admin-btn-warning" title="Редагувати">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="admin-btn-danger" onclick="return confirm('Видалити цього учня?')" title="Видалити">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @include('admin.students.partials.student_modal', ['student' => $student])
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('toggle-student-form')?.addEventListener('click', function () {
                document.getElementById('student-form-container')?.classList.toggle('d-none');
            });

            const lang = { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json' };
            const dom =
                "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

            const activeDT = $('#active-students-table').length ? $('#active-students-table').DataTable({
                language: lang, pageLength: 10, lengthMenu: [5, 10, 25, 50], order: [[0, 'asc']], dom
            }) : null;

            const inactiveDT = $('#inactive-students-table').length ? $('#inactive-students-table').DataTable({
                language: lang, pageLength: 10, lengthMenu: [5, 10, 25, 50], order: [[0, 'asc']], dom
            }) : null;

            const searchInput = document.getElementById('studentSearch');
            searchInput?.addEventListener('keyup', function () {
                const val = this.value;
                if (activeDT) activeDT.search(val).draw();
                if (inactiveDT) inactiveDT.search(val).draw();
            });
        });
    </script>

    <script>
        window.activeStudentIds = @json($activeStudents->pluck('id'));
    </script>
@endsection
