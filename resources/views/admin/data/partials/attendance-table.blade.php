<div>
    <form id="attendanceFilterForm" method="GET" class="mb-3 d-flex align-items-center gap-2">


        <label class="form-label mb-0" for="month">Місяць:</label>
        <select name="month" id="month" class="form-select">
            @for ($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                </option>
            @endfor
        </select>

        <label class="form-label mb-0" for="year">Рік:</label>
        <select name="year" id="year" class="form-select">
            @for ($y = now()->year; $y >= 2022; $y--)
                <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <div id="salaryTableWrapper">
        @if($students->count() > 0)
            <div class="table-responsive shadow rounded">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Ім'я</th>
                        <th>Прізвище</th>
                        <th>Викладач</th>
                        <th>Абонемент</th>
                        <th>Занять цього місяця</th>
                        <th>Загальна кількість</th>
                        <th>Поразові оплати</th>
                        <th>Відвідуваність</th>

                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $student)
                        <tr>
                            <td>{{ $student->first_name }}</td>
                            <td>{{ $student->last_name }}</td>
                            <td>{{ $student->teacher->full_name ?? '—' }}</td>
                            <td>@if($student->subscriptionTemplate)
                                    {{ $student->subscriptionTemplate->title }}
                                    ({{ $student->subscriptionTemplate->lessons_per_week }} р/т)
                                    ({{ $student->subscriptionTemplate->price }}грн)
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $monthLessonsCount[$student->id] ?? 0 }}</td>
                            <td>{{ $totalLessonsCount[$student->id] ?? 0 }}</td>
                            <td>{{ $singlePaymentsCount[$student->id] ?? 0 }}</td>
                            <td>
                                <button class="btn btn-link p-0 student-calendar-btn" data-student-id="{{ $student->id }}" data-student-name="{{ $student->full_name }}">
                                    КАЛЕНДАР
                                </button>
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const form = document.getElementById('attendanceFilterForm');
                        form.querySelectorAll('select').forEach(select => {
                            select.addEventListener('change', () => form.submit());
                        });
                    });
                </script>

            </div>
        @else
            <div class="alert alert-info">Студенти не знайдені.</div>
        @endif

    </div>
</div>
<!-- Модальне вікно -->
<div class="modal fade" id="studentCalendarModal" tabindex="-1" aria-labelledby="studentCalendarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentCalendarLabel">Календар відвідуваності</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div id="studentCalendar"></div>
            </div>
        </div>
    </div>
</div>

