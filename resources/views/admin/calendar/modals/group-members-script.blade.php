<script>


    function openGroupModal({lessonId, lessonDate, lessonTime, groupId, members }) {

        document.getElementById('lessonId').value = lessonId;
        document.getElementById('lessonDate').value = lessonDate;
        document.getElementById('lessonTime').value = lessonTime;

        const membersList = document.getElementById('groupMembersList');
        membersList.dataset.groupId = groupId;

        membersList.innerHTML = '';
        members.forEach(member => {
            const li = document.createElement('li');
            li.classList.add('list-group-item');
            li.dataset.id = member.id;
            li.textContent = member.name;
            membersList.appendChild(li);
        });

        // Встановлюємо data-lesson-id для кнопки переносу
        const markGroupRescheduledBtn = document.getElementById('markGroupRescheduledBtn');
        if (markGroupRescheduledBtn) {
            markGroupRescheduledBtn.setAttribute('data-lesson-id', lessonId);
        }

        const modalEl = document.getElementById('groupMembersModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        document.getElementById('attendanceForm').classList.add('d-none');
    }



    document.addEventListener('DOMContentLoaded', () => {

        /*** ✅ Обробка кнопки "Проведено" (відкрити чекбокси) ***/
        document.getElementById('markCompletedBtn').addEventListener('click', () => {
            const membersList = document.getElementById('groupMembersList');
            const checkboxesContainer = document.getElementById('attendanceCheckboxes');
            checkboxesContainer.innerHTML = '';

            membersList.querySelectorAll('li').forEach(li => {
                const studentId = li.dataset.id;
                const studentName = li.textContent;

                const item = document.createElement('div');
                item.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');

                item.innerHTML = `
                    <span>${studentName}</span>
                    <input class="form-check-input" type="checkbox" name="students[]" value="${studentId}" id="student-${studentId}">
                `;

                checkboxesContainer.appendChild(item);
            });

            document.getElementById('attendanceForm').classList.remove('d-none');
        });

        /*** ✅ Обробка відправки присутності ***/
        document.getElementById('attendanceFormList').addEventListener('submit', async (e) => {
            e.preventDefault();

            const checkedBoxes = document.querySelectorAll('#attendanceCheckboxes input[type="checkbox"]:checked');
            const presentStudents = Array.from(checkedBoxes).map(cb => cb.value);

            const groupId = document.getElementById('groupMembersList').dataset.groupId;
            const lessonId = document.getElementById('lessonId').value;
            const date = document.getElementById('lessonDate').value;
            const time = document.getElementById('lessonTime').value;


            try {

                const response = await fetch('/admin/calendar/group-attendance', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        group_id: groupId,
                        lesson_id: lessonId,
                        date: date,
                        time: time,
                        present_students: presentStudents
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Відвідуваність збережена!');
                    bootstrap.Modal.getInstance(document.getElementById('groupMembersModal')).hide();

                    if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                        window.calendar.refetchEvents();
                    }
                } else {
                    alert(data.message || 'Сталася помилка');
                }
            } catch (error) {
                console.error('Помилка збереження відвідуваності:', error);
                alert('Помилка збереження відвідуваності');
            }
        });

            /*** 🔁 Кнопка "Перенести" відкриває модалку переносу (для групи) ***/
        /*** 🔄 Відкрити модалку переносу заняття ***/
        const markGroupRescheduledBtn = document.getElementById('markGroupRescheduledBtn');
        markGroupRescheduledBtn.addEventListener('click', () => {
            const lessonId = document.getElementById('lessonId').value;
            const lessonDate = document.getElementById('lessonDate').value;
            const lessonTime = document.getElementById('lessonTime').value;

            // Записуємо oldDate/Time у data-атрибути модалки
            const rescheduleModalEl = document.getElementById('groupRescheduleModal');
            rescheduleModalEl.dataset.oldDate = lessonDate;
            rescheduleModalEl.dataset.oldTime = lessonTime;

            // Передаємо ID у приховане поле
            document.getElementById('groupRescheduleLessonId').value = lessonId;
            document.getElementById('groupNewDate').value = lessonDate;
            document.getElementById('groupNewTime').value = lessonTime;

            // Ховаємо попередню модалку
            const groupModalEl = document.getElementById('groupMembersModal');
            const groupModal = bootstrap.Modal.getInstance(groupModalEl);
            if (groupModal) groupModal.hide();

            // Показуємо форму переносу
            const rescheduleModal = new bootstrap.Modal(rescheduleModalEl);
            rescheduleModal.show();
        });


        /*** 🕓 Обробка форми переносу групи ***/
        const groupRescheduleForm = document.getElementById('groupRescheduleForm');
        groupRescheduleForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const lessonId = document.getElementById('groupRescheduleLessonId').value;
            const newDate = document.getElementById('groupNewDate').value;
            const newTime = document.getElementById('groupNewTime').value;

            const modalEl = document.getElementById('groupRescheduleModal');
            const oldDate = modalEl.dataset.oldDate;
            const oldTime = modalEl.dataset.oldTime;

            const groupId = document.getElementById('groupMembersList').dataset.groupId;

            try {
                const response = await fetch(`/admin/calendar/group-lessons/${lessonId}/reschedule`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        lesson_id: lessonId,
                        group_id: groupId,
                        new_date: newDate,
                        new_time: newTime,
                        date: oldDate,
                        time: oldTime
                    })
                });

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Помилка парсингу JSON:', e);
                    alert('Сервер повернув некоректний JSON');
                    return;
                }

                if (data.success) {
                    alert('Групове заняття перенесено успішно!');
                    bootstrap.Modal.getInstance(modalEl).hide();

                    if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                        window.calendar.refetchEvents();
                    } else {
                        console.warn('calendar ще не готова');
                    }
                } else {
                    alert(data.message || 'Сталася помилка');
                }
            } catch (error) {
                console.error('Помилка при перенесенні групового заняття:', error);
                alert('Помилка при перенесенні групового заняття');
            }
        });

        /*** 🟥 Кнопка "Скасовано" — логіка ще не реалізована ***/
        document.getElementById('markCancelledBtn').addEventListener('click', async () => {
            if (!confirm('Ви впевнені, що хочете скасувати заняття для цієї групи?')) return;

            const groupId = document.getElementById('groupMembersList').dataset.groupId;
            const lessonId = document.getElementById('lessonId').value;
            const date = document.getElementById('lessonDate').value;
            const time = document.getElementById('lessonTime').value;

            if (!lessonId) {
                alert('Помилка: lessonId не визначено');
                return;
            }


            try {
                const response = await fetch(`/admin/calendar/group-lessons/${lessonId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',                 // ← важливо
                        'X-Requested-With': 'XMLHttpRequest'          // ← важливо
                    },
                    body: JSON.stringify({
                        lesson_id: lessonId,
                        group_id: groupId,
                        date: date,
                        time: time
                    })
                });

                // Перевірка типу відповіді — чи це дійсно JSON
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Очікував JSON, але отримав HTML або інший тип:', text.slice(0, 300));
                    alert('Сервер повернув не JSON. Можливо, сталася помилка або редірект.');
                    return;
                }

                const data = await response.json();

                if (!response.ok) {
                    console.error('Сервер повернув помилку:', response.status, data);
                    alert(data.message || 'Сталася помилка на сервері');
                    return;
                }

                if (data.success) {
                    alert('Заняття скасовано!');
                    const modal = document.getElementById('groupMembersModal');
                    if (modal) {
                        const instance = bootstrap.Modal.getInstance(modal);
                        if (instance) instance.hide();
                    }

                    if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                        window.calendar.refetchEvents();
                    }
                } else {
                    alert(data.message || 'Сталася помилка');
                }
            } catch (error) {
                console.error('Помилка при скасуванні заняття:', error);
                alert('Помилка при скасуванні заняття');
            }


        });

    });
</script>
