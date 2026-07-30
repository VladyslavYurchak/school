<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const addEventModal = new bootstrap.Modal(document.getElementById('addEventModal'));
        const manageEventModal = new bootstrap.Modal(document.getElementById('manageEventModal'));
        const editEventModal = new bootstrap.Modal(document.getElementById('editEventModal'));

        const addEventForm = document.getElementById('addEventForm');
        const editEventForm = document.getElementById('editEventForm');

        // Змінна для перенесення уроків
        const rescheduleModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
        const rescheduleForm = document.getElementById('rescheduleForm');

        let selectedEventId = null;
        let selectedEventStart = null;
        let selectedEventEnd = null;

        function formatDateInput(date) {
            return [
                date.getFullYear(),
                String(date.getMonth() + 1).padStart(2, '0'),
                String(date.getDate()).padStart(2, '0')
            ].join('-');
        }

        function formatTimeInput(date) {
            return [
                String(date.getHours()).padStart(2, '0'),
                String(date.getMinutes()).padStart(2, '0')
            ].join(':');
        }

        function minutesBetween(start, end) {
            if (!start || !end) {
                return 60;
            }

            return Math.max(15, Math.round((end.getTime() - start.getTime()) / 60000));
        }

        window.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'uk',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: '/admin/calendar-events',
            selectable: true,

            dateClick: function(info) {
                const clickedDate = new Date(info.dateStr);

                // Витягуємо дату у форматі yyyy-mm-dd
                const formattedDate = clickedDate.toISOString().slice(0, 10);
                document.getElementById('eventDate').value = formattedDate;

                document.getElementById('eventTime').value = '09:00';

                addEventModal.show();
            },


            select: function(info) {
                const onlyDate = info.startStr.slice(0, 10);
                document.getElementById('eventDate').value = onlyDate;

                // Типово виставляємо 09:00, якщо час не відомий
                document.getElementById('eventTime').value = '09:00';

                addEventModal.show();
            },


            eventClick: function(info) {
                selectedEventId = info.event.id;
                selectedEventStart = info.event.start;
                selectedEventEnd = info.event.end;

                const groupId = info.event.extendedProps.group_id;
                const studentId = info.event.extendedProps.student_id;
                const members = info.event.extendedProps.members || [];

                if (groupId) {
                    // якщо з бекенду прийшли учні — відкриваємо одразу
                    if (Array.isArray(members) && members.length > 0) {
                        openGroupModal({
                            lessonId: info.event.extendedProps.lesson_id || info.event.id,
                            lessonDate: info.event.startStr.split('T')[0],
                            lessonTime: info.event.startStr.split('T')[1]?.slice(0, 5) || '',
                            groupId: groupId,
                            members: members
                        });
                    } else {
                        // fallback: тягнемо склад групи з API
                        (async () => {
                            try {
                                const resp = await fetch(`/admin/calendar-events/${groupId}/members`, {
                                    method: 'GET',
                                    headers: { 'Accept': 'application/json' }
                                });
                                if (!resp.ok) throw new Error('Failed to load group members');
                                const data = await resp.json();

                                const fetched = (data.members || []).map(m => ({
                                    id: m.id,
                                    name: [m.last_name, m.first_name].filter(Boolean).join(' ').trim()
                                }));

                                openGroupModal({
                                    lessonId: info.event.extendedProps.lesson_id || info.event.id,
                                    lessonDate: info.event.startStr.split('T')[0],
                                    lessonTime: info.event.startStr.split('T')[1]?.slice(0, 5) || '',
                                    groupId: groupId,
                                    members: fetched
                                });
                            } catch (e) {
                                console.error('Cannot load group members:', e);
                                alert('Не вдалося завантажити склад групи.');
                            }
                        })();
                    }
                } else {
                    document
                        .getElementById('cancelStudentFutureLessons')
                        .classList.toggle('d-none', !studentId);

                    // Старий режим для індивідуального заняття
                    document.getElementById('manageEventTitle').textContent = info.event.title;
                    document.getElementById('manageEventDate').textContent = info.event.start.toLocaleDateString('uk-UA');
                    document.getElementById('manageEventTime').textContent = info.event.start.toLocaleTimeString('uk-UA', { hour: '2-digit', minute: '2-digit' });
                    manageEventModal.show();
                }
            }

        });

        window.calendar.render();

        // --- Додавання заняття ---
        addEventForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const data = {
                date: document.getElementById('eventDate').value,
                time: document.getElementById('eventTime').value,
                duration: document.getElementById('eventDuration').value,
                notes: document.getElementById('eventNotes').value,
                student_id: document.getElementById('eventStudent').value || null,
                group_id: document.getElementById('eventGroup').value || null,
                repeat_period: document.getElementById('repeatPeriod').value,
                lesson_type: document.querySelector('input[name="lesson_type"]:checked').value
            };


            // Парсимо дату і час
            const start = new Date(data.date + 'T' + data.time);
            const end = new Date(start.getTime() + data.duration * 60000);

            // Функція для форматування дати у локальному часі
            function formatDateTimeLocal(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            }

            // Формуємо payload
            const payload = {
                start: formatDateTimeLocal(start),
                end: formatDateTimeLocal(end),
                duration: data.duration,
                notes: data.notes,
                repeat_period: data.repeat_period,
                lesson_type: data.lesson_type, // додаємо тут
                student_id: null,
                group_id: null
            };

            // Визначаємо тип заняття і додаємо потрібний ID
            if (data.lesson_type === 'individual') {
                if (!data.student_id) {
                    alert('Будь ласка, оберіть студента');
                    return;
                }
                payload.student_id = data.student_id;
            } else if (data.lesson_type === 'group' || data.lesson_type === 'pair') {
                if (!data.group_id) {
                    alert('Будь ласка, оберіть групу');
                    return;
                }
                payload.group_id = data.group_id;
            } else if (data.lesson_type === 'trial') {
                // Пробне заняття — student_id та group_id залишаються null
            }



            fetch('/admin/calendar-events', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            }).then(res => res.json()).then(response => {
                if (response.success) {
                    calendar.refetchEvents();
                    addEventForm.reset();
                    syncLessonTypeFields();
                    addEventModal.hide();
                } else {
                    alert(response.message || 'Сталася помилка при додаванні заняття');
                }
            });
        });


        // --- Маркування як проведене ---
        document.getElementById('markAsCompleted').addEventListener('click', function () {
            updateStatus('complete', 'Заняття відмічене як проведене');
        });

        // --- Маркування як перенесене ---
        document.getElementById('markAsRescheduled').addEventListener('click', function () {
            manageEventModal.hide();

            const eventDate = document.getElementById('manageEventDate').textContent.trim(); // "14.07.2025"
            const eventTime = document.getElementById('manageEventTime').textContent;

            const parts = eventDate.split('.');
            if (parts.length === 3) {
                // Формуємо ISO рядок yyyy-mm-dd
                const isoDate = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                document.getElementById('newDate').value = isoDate;
            } else {
                document.getElementById('newDate').value = '';
                console.warn('Невірний формат дати в manageEventDate:', eventDate);
            }

            document.getElementById('newTime').value = eventTime;
            rescheduleModal.show();
        });

        // --- Маркування як скасоване ---
        document.getElementById('markAsCancelled').addEventListener('click', function () {
            updateStatus('cancel', 'Заняття скасоване');
        });

        document.getElementById('cancelStudentFutureLessons').addEventListener('click', function () {
            const confirmed = window.confirm(
                'Скасувати це заняття та всі наступні індивідуальні заняття цього учня? Цю дію неможливо скасувати.'
            );

            if (!confirmed) {
                return;
            }

            updateStatus(
                'cancel',
                'Це та всі наступні заняття учня скасовані',
                { scope: 'student_future' }
            );
        });


        // --- Редагування часу заняття ---
        document.getElementById('editEvent').addEventListener('click', function () {
            if (!selectedEventStart) {
                alert('Не вдалося визначити час заняття');
                return;
            }

            document.getElementById('editEventDate').value = formatDateInput(selectedEventStart);
            document.getElementById('editEventTime').value = formatTimeInput(selectedEventStart);
            document.getElementById('editEventDuration').value = minutesBetween(selectedEventStart, selectedEventEnd);

            manageEventModal.hide();
            editEventModal.show();
        });

        editEventForm.addEventListener('submit', function (e) {
            e.preventDefault();

            fetch(`/admin/calendar-events/${selectedEventId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    date: document.getElementById('editEventDate').value,
                    time: document.getElementById('editEventTime').value,
                    duration: document.getElementById('editEventDuration').value
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    calendar.refetchEvents();
                    editEventModal.hide();
                    alert('Заняття оновлено');
                } else {
                    alert(data.message || 'Помилка оновлення заняття');
                }
            });
        });

        // --- Перенесення заняття ---
        rescheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const newDate = document.getElementById('newDate').value;
            const newTime = document.getElementById('newTime').value;
            const initiator = document.getElementById('initiator').value;

            fetch(`/admin/calendar-events/${selectedEventId}/reschedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    new_date: newDate,
                    new_time: newTime,
                    initiator: initiator
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    calendar.refetchEvents();
                    rescheduleModal.hide();
                    alert('Заняття перенесено');
                } else {
                    alert(data.message || 'Помилка перенесення');
                }
            });
        });

        // --- Функція оновлення статусу ---
        function updateStatus(action, message, extraData = {}) {
            fetch(`/admin/calendar-events/${selectedEventId}/${action}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(extraData)
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    calendar.refetchEvents();
                    manageEventModal.hide();
                    alert(message);
                } else {
                    alert(data.message || 'Помилка виконання дії');
                }
            });
        }

        // --- Перемикання типу заняття у формі ---
        const lessonTypeRadios = document.querySelectorAll('input[name="lesson_type"]');
        const studentSelectWrapper = document.getElementById('studentSelectContainer');
        const groupSelectWrapper = document.getElementById('groupSelectContainer');


        const groupSelect = document.getElementById('eventGroup');
        const studentSelect = document.getElementById('eventStudent');

        function filterGroupsByType(type) {
            [...groupSelect.options].forEach(option => {
                if (!option.value) return;

                const groupType = option.dataset.type;

                option.hidden = groupType !== type;
                option.disabled = groupType !== type;
            });

            groupSelect.value = '';
        }

        function syncLessonTypeFields() {
            const checkedLessonType = document.querySelector('input[name="lesson_type"]:checked');
            const type = checkedLessonType ? checkedLessonType.value : 'individual';

            studentSelectWrapper.classList.toggle('d-none', type !== 'individual');
            groupSelectWrapper.classList.toggle('d-none', !(type === 'group' || type === 'pair'));

            if (type === 'group' || type === 'pair') {
                studentSelect.value = '';
                filterGroupsByType(type);
            } else {
                groupSelect.value = '';
            }
        }

        lessonTypeRadios.forEach(radio => {
            radio.addEventListener('change', syncLessonTypeFields);
        });


// Викликаємо зміну при завантаженні, щоб встановити правильний стан
        syncLessonTypeFields();
    });
</script>
