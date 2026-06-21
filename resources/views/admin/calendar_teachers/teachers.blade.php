@extends('admin.layouts.layout')

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css">
@endsection

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-calendar-range"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Календар викладачів</h1>
                        <p class="admin-subtitle">Загальний перегляд занять усіх викладачів із фільтром по конкретному викладачу.</p>
                    </div>

                    <div class="admin-actions">
                        <span class="admin-badge admin-badge-muted">Викладачів: {{ $teachers->count() }}</span>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Фільтр</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-filter-grid">
                        <div class="admin-field">
                            <label for="teacher-filter">Викладач</label>
                            <select id="teacher-filter" class="form-select">
                                <option value="">Усі викладачі</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Календар занять</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-calendar-shell">
                        <div id="calendar"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const teacherFilter = document.getElementById('teacher-filter');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'uk',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                editable: false,
                selectable: false,
                events: function (info, successCallback, failureCallback) {
                    const params = new URLSearchParams({
                        start: info.startStr,
                        end: info.endStr,
                    });

                    if (teacherFilter.value) {
                        params.append('teacher_id', teacherFilter.value);
                    }

                    fetch('{{ route('admin.calendar_teachers.teachers.events') }}?' + params.toString())
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => failureCallback(error));
                },
                eventDidMount: function (info) {
                    info.el.setAttribute('title', info.event.title);
                },
            });

            calendar.render();

            teacherFilter.addEventListener('change', function () {
                calendar.refetchEvents();
            });
        });
    </script>
@endpush
