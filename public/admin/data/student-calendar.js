document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('studentCalendarModal');
    const modal = new bootstrap.Modal(modalEl);
    let calendar;

    document.querySelectorAll('.student-calendar-btn').forEach(button => {
        button.addEventListener('click', function () {
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;

            document.getElementById('studentCalendarLabel').textContent = 'Відвідуваність: ' + studentName;

            fetch(`/admin/data/student-attendance/${studentId}`)
                .then(response => response.json())
                .then(data => {
                    // Відкрити модалку
                    modal.show();

                    // Почистити календар, якщо є
                    if (calendar) {
                        calendar.destroy();
                    }

                    // Ініціалізація календаря після відкриття модалки
                    modalEl.addEventListener('shown.bs.modal', function onShown() {
                        const calendarEl = document.getElementById('studentCalendar');
                        calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            locale: 'uk',
                            height: 500,
                            events: data,
                            eventColor: '#5ac3a4',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: ''
                            },
                        });
                        calendar.render();

                        // Видаляємо слухача, щоб не створювати кілька разів
                        modalEl.removeEventListener('shown.bs.modal', onShown);
                    });
                })
                .catch(error => {
                    alert('Помилка завантаження календаря');
                    console.error(error);
                });
        });
    });
});


