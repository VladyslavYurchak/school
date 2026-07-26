<div>
    <div id="attendanceTableWrapper">
        @if($students->count() > 0)
            <div class="table-responsive shadow rounded">
                <table class="table table-bordered table-striped align-middle admin-data-table admin-data-table-attendance">
                    <thead>
                    <tr>
                        <th>Ім'я</th>
                        <th>Прізвище</th>
                        <th>Викладач</th>
                        <th>Абонемент</th>
                        <th>Занять цього місяця</th>
                        <th>Загальна кількість</th>
                        <th>Відвідуваність</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $student)
                        @php
                            $sid = $student->id;
                            $studentFullName = $student->full_name ?? ($student->first_name . ' ' . $student->last_name);
                            $monthlySubscription = $monthlySubscriptions->get($sid);
                        @endphp
                        <tr>
                            <td>{{ $student->first_name }}</td>
                            <td>{{ $student->last_name }}</td>
                            <td>{{ $monthlySubscription?->teacher?->full_name ?? $student->teacher?->full_name ?? '—' }}</td>
                            <td>
                                @if($monthlySubscription)
                                    {{ $monthlySubscription->subscription_title ?? $monthlySubscription->subscriptionTemplate?->title ?? 'Абонемент' }}
                                    @if($monthlySubscription->subscription_lessons_per_week ?? $monthlySubscription->subscriptionTemplate?->lessons_per_week)
                                        ({{ $monthlySubscription->subscription_lessons_per_week ?? $monthlySubscription->subscriptionTemplate?->lessons_per_week }} р/т)
                                    @endif
                                    ({{ number_format($monthlySubscription->price, 2, ',', ' ') }} грн)
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $monthLessonsCount[$sid] ?? 0 }}</td>
                            <td>{{ $totalLessonsCount[$sid] ?? 0 }}</td>
                            <td>
                                <button class="btn btn-link p-0 student-calendar-btn"
                                        data-student-id="{{ $sid }}"
                                        data-student-name="{{ $studentFullName }}"
                                        data-calendar-date="{{ sprintf('%04d-%02d-01', $selectedYear, $selectedMonth) }}">
                                    КАЛЕНДАР
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

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
