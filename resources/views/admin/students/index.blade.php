@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap">
                <h2>Учні</h2>

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
            <h4>Активні студенти</h4>

            <div class="card shadow-sm mb-5">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
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
                                <td>
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
            <h4>Неактивні студенти</h4>
            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
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
                                <td>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('toggle-student-form')?.addEventListener('click', function () {
                document.getElementById('student-form-container')?.classList.toggle('d-none');
            });
        });
    </script>
    <script>
        window.activeStudentIds = @json($activeStudents->pluck('id'));
    </script>
    <script src="{{ asset('admin/students/payment.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('studentSearch');
            const tableRows = document.querySelectorAll('.card-body table tbody tr');

            searchInput.addEventListener('keyup', function () {
                const value = this.value.toLowerCase();

                tableRows.forEach(row => {
                    const name = row.cells[1]?.textContent.toLowerCase() || '';
                    const surname = row.cells[0]?.textContent.toLowerCase() || '';
                    if (name.includes(value) || surname.includes(value)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>




@endsection
@section('styles')
    <link href="{{ asset('admin/students/payment.css') }}" rel="stylesheet">
@endsection

