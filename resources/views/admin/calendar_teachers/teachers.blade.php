@extends('admin.layouts.layout') {{-- Заміни на свій реальний шлях до layout, якщо інший --}}

@section('styles')
    {{-- FullCalendar CSS --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css">

    <style>
        /* Загальні стилі календаря */
        #calendar {
            min-height: 600px;
            font-family: "Inter", sans-serif;
        }

        /* =============================== */
        /* Стиль подій у місячному режимі */
        /* =============================== */
        .fc-daygrid-event {
            padding: 3px 6px !important;
            border-radius: 6px !important;
            white-space: normal !important;
            line-height: 1.25;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: 0.15s ease-in-out;
        }

        /* Ховер ефект */
        .fc-daygrid-event:hover {
            filter: brightness(0.93);
            transform: translateY(-1px);
        }

        /* текст всередині події */
        .fc-daygrid-event .fc-event-title {
            white-space: normal !important;
        }

        /* =============================== */
        /* Стиль подій у тижневому режимі */
        /* =============================== */
        .fc-timegrid-event {
            border-radius: 6px !important;
            overflow: hidden;
            cursor: pointer;
            transition: 0.15s;
        }

        .fc-timegrid-event:hover {
            filter: brightness(0.92);
            transform: translateY(-1px);
        }

        .fc-timegrid-event .fc-event-main {
            padding: 4px 6px !important;
            white-space: normal !important;
            line-height: 1.2;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* =============================== */
        /* Заголовки днів місяця */
        /* =============================== */
        .fc-col-header-cell-cushion {
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 0 !important;
        }

        /* Номери днів */
        .fc-daygrid-day-number {
            font-size: 0.9rem;
            padding: 4px 6px !important;
            font-weight: 600;
        }

        /* =============================== */
        /* Сьогоднішній день */
        /* =============================== */
        .fc-day-today {
            background: #FFF4CC !important;
        }

        .fc-day-today .fc-daygrid-day-number {
            color: #d48806 !important;
            font-weight: 700;
        }

        /* =============================== */
        /* Події без кольору (default) */
        /* =============================== */
        .fc-event {
            border: none !important;
        }
        /* Сіра шапка картки замість бірюзової */
        .card-header {
            background-color: #f0f0f0 !important; /* світло-сірий */
            color: #333 !important;               /* темний текст */
            border-bottom: 1px solid #dcdcdc !important;
        }

        .fc .fc-button-primary {
            background: #343a40 !important;      /* 🔥 колір sidebar */
            border-color: #343a40 !important;
            color: #fff !important;
            box-shadow: none !important;
        }

        /* Hover */
        .fc .fc-button-primary:hover {
            background: #4b545c !important;      /* трішки світліший для hover */
            border-color: #4b545c !important;
        }

        /* Активна кнопка (наприклад Month) */
        .fc .fc-button-primary.fc-button-active {
            background: #1f2d3d !important;      /* темніший */
            border-color: #1f2d3d !important;
        }

        /* Неактивна, але в фокусі */
        .fc .fc-button-primary:focus {
            background: #343a40 !important;
            box-shadow: 0 0 0 0.15rem rgba(52, 58, 64, 0.4) !important;
        }

        /* Disabled (коли нема куди листати) */
        .fc .fc-button-primary:disabled {
            opacity: .45;
            cursor: default;
        }

        /* =============================== */
        /* Панель навігації угорі */
        /* =============================== */
        .fc-toolbar-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .fc-button-primary {
            background: #4a6cf7 !important;
            border-color: #4a6cf7 !important;
            transition: 0.15s;
        }

        .fc-button-primary:hover {
            filter: brightness(0.9);
        }
    </style>

@endsection

@section('content')
    <main class="app-main">

        <!-- Основний вміст -->
        <div class="app-content">
            <div class="container-fluid">

                {{-- Фільтр по викладачу --}}
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="teacher-filter" class="form-select">
                            <option value="">Усі викладачі</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Календар у картці AdminLTE --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Календар занять</h3>
                            </div>
                            <div class="card-body">
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- /.container-fluid -->
        </div> <!-- /.app-content -->
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const teacherFilter = document.getElementById('teacher-filter');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',   // 🔹 одразу місяць
                locale: 'uk',
                height: 'auto',

                headerToolbar: {               // 🔹 верхня панель керування
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

                // 🔹 Показувати повну інформацію як tooltip при наведенні
                eventDidMount: function (info) {
                    // title вже містить: назва — вчитель — студент
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
