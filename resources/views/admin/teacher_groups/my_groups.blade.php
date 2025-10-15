@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Мої групи</h2>
                {{-- Кнопка створення групи, якщо буде потрібно --}}
                {{-- <a href="{{ route('admin.groups.create') }}" class="btn btn-success">+ Додати групу</a> --}}
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($groups->isEmpty())
                <div class="alert alert-info">
                    У вас немає груп.
                </div>
            @else
                <div class="card shadow-sm">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="groups-table" style="cursor: pointer;">
                            <thead class="table-light">
                            <tr>
                                <th>Назва групи</th>
                                <th>Кількість студентів</th>
                                <th>Нотатки</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groups as $group)
                                <tr data-group="{{ json_encode([
                                'name' => $group->name,
                                'notes' => $group->notes,
                                'students' => $group->students->map(fn($s) => [
                                    'first_name' => $s->first_name,
                                    'last_name' => $s->last_name,
                                    'phone' => $s->phone,
                                    'email' => $s->email,
                                ]),
                            ]) }}">
                                    <td>{{ $group->name }}</td>
                                    <td>{{ $group->students_count ?? $group->students->count() }}</td>
                                    <td class="text-truncate" style="max-width: 300px;">{{ $group->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </main>

    {{-- Модальне вікно --}}
    <div class="modal fade" id="groupDetailsModal" tabindex="-1" aria-labelledby="groupDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupDetailsModalLabel">Інформація про групу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>
                <div class="modal-body">
                    <h4 id="modalGroupName"></h4>
                    <p id="modalGroupNotes" class="mb-3"></p>

                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Ім'я</th>
                            <th>Прізвище</th>
                            <th>Телефон</th>
                            <th>Email</th>
                        </tr>
                        </thead>
                        <tbody id="modalStudentsBody">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            const modal = new bootstrap.Modal(document.getElementById('groupDetailsModal'));
            const $modalGroupName = $('#modalGroupName');
            const $modalGroupNotes = $('#modalGroupNotes');
            const $modalStudentsBody = $('#modalStudentsBody');

            $('#groups-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']],
            });

            $('#groups-table tbody').on('click', 'tr', function () {
                const groupData = $(this).data('group');

                $modalGroupName.text(groupData.name);
                $modalGroupNotes.text(groupData.notes ?? '');

                $modalStudentsBody.empty();

                if(groupData.students.length === 0) {
                    $modalStudentsBody.append('<tr><td colspan="4" class="text-center">Немає студентів у цій групі</td></tr>');
                } else {
                    groupData.students.forEach(student => {
                        $modalStudentsBody.append(`
                    <tr>
                        <td>${student.first_name}</td>
                        <td>${student.last_name}</td>
                        <td>${student.phone ?? '-'}</td>
                        <td>${student.email ?? '-'}</td>
                    </tr>
                `);
                    });
                }

                modal.show();
            });
        });
    </script>
@endsection
