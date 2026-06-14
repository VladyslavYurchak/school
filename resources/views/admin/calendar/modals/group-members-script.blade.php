<script>
    function openGroupModal({ lessonId, lessonDate, lessonTime, groupId, members }) {
        setValue('lessonId', lessonId);
        setValue('lessonDate', lessonDate);
        setValue('lessonTime', lessonTime);
        setValue('groupRescheduleLessonId', lessonId);

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

        document.getElementById('attendanceForm').classList.add('d-none');
        showModal('groupMembersModal');
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('markCompletedBtn').addEventListener('click', showAttendanceForm);
        document.getElementById('attendanceFormList').addEventListener('submit', saveGroupAttendance);
        document.getElementById('markGroupRescheduledBtn').addEventListener('click', openGroupRescheduleModal);
        document.getElementById('groupRescheduleForm').addEventListener('submit', saveGroupReschedule);
        document.getElementById('markCancelledBtn').addEventListener('click', cancelGroupLesson);
    });

    function showAttendanceForm() {
        const checkboxesContainer = document.getElementById('attendanceCheckboxes');
        checkboxesContainer.innerHTML = '';

        getGroupMemberItems().forEach(li => {
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
    }

    async function saveGroupAttendance(e) {
        e.preventDefault();

        try {
            const data = await postJson('/admin/calendar/group-attendance', {
                group_id: currentGroupId(),
                lesson_id: currentLessonId(),
                date: valueOf('lessonDate'),
                time: valueOf('lessonTime'),
                present_students: selectedStudentIds()
            });

            if (!data.success) {
                alert(data.message || 'Сталася помилка');
                return;
            }

            alert('Відвідуваність збережена!');
            hideModal('groupMembersModal');
            refreshCalendar();
        } catch (error) {
            handleRequestError(error, 'Помилка збереження відвідуваності');
        }
    }

    function openGroupRescheduleModal() {
        const lessonDate = valueOf('lessonDate');
        const lessonTime = valueOf('lessonTime');
        const modalEl = document.getElementById('groupRescheduleModal');

        modalEl.dataset.oldDate = lessonDate;
        modalEl.dataset.oldTime = lessonTime;

        setValue('groupRescheduleLessonId', currentLessonId());
        setValue('groupNewDate', lessonDate);
        setValue('groupNewTime', lessonTime);

        hideModal('groupMembersModal');
        showModal('groupRescheduleModal');
    }

    async function saveGroupReschedule(e) {
        e.preventDefault();

        const lessonId = valueOf('groupRescheduleLessonId');
        const modalEl = document.getElementById('groupRescheduleModal');

        try {
            const data = await postJson(`/admin/calendar/group-lessons/${lessonId}/reschedule`, {
                lesson_id: lessonId,
                group_id: currentGroupId(),
                new_date: valueOf('groupNewDate'),
                new_time: valueOf('groupNewTime'),
                date: modalEl.dataset.oldDate,
                time: modalEl.dataset.oldTime
            });

            if (!data.success) {
                alert(data.message || 'Сталася помилка');
                return;
            }

            alert('Групове заняття перенесено успішно!');
            hideModal('groupRescheduleModal');
            refreshCalendar();
        } catch (error) {
            handleRequestError(error, 'Помилка при перенесенні групового заняття');
        }
    }

    async function cancelGroupLesson() {
        if (!confirm('Ви впевнені, що хочете скасувати заняття для цієї групи?')) {
            return;
        }

        const lessonId = currentLessonId();

        if (!lessonId) {
            alert('Помилка: lessonId не визначено');
            return;
        }

        try {
            const data = await postJson(`/admin/calendar/group-lessons/${lessonId}/cancel`, {
                lesson_id: lessonId,
                group_id: currentGroupId(),
                date: valueOf('lessonDate'),
                time: valueOf('lessonTime')
            }, {
                'X-Requested-With': 'XMLHttpRequest'
            });

            if (!data.success) {
                alert(data.message || 'Сталася помилка');
                return;
            }

            alert('Заняття скасовано!');
            hideModal('groupMembersModal');
            refreshCalendar();
        } catch (error) {
            handleRequestError(error, 'Помилка при скасуванні заняття');
        }
    }

    async function postJson(url, payload, extraHeaders = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                ...extraHeaders
            },
            body: JSON.stringify(payload)
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : {};

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    }

    function handleRequestError(error, fallbackMessage) {
        console.error(fallbackMessage, error);
        alert(error.message || fallbackMessage);
    }

    function refreshCalendar() {
        if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
            window.calendar.refetchEvents();
        }
    }

    function showModal(id) {
        new bootstrap.Modal(document.getElementById(id)).show();
    }

    function hideModal(id) {
        const modal = bootstrap.Modal.getInstance(document.getElementById(id));

        if (modal) {
            modal.hide();
        }
    }

    function currentGroupId() {
        return document.getElementById('groupMembersList').dataset.groupId;
    }

    function currentLessonId() {
        return valueOf('lessonId');
    }

    function selectedStudentIds() {
        return Array.from(document.querySelectorAll('#attendanceCheckboxes input[type="checkbox"]:checked'))
            .map(cb => cb.value);
    }

    function getGroupMemberItems() {
        return document.getElementById('groupMembersList').querySelectorAll('li');
    }

    function valueOf(id) {
        return document.getElementById(id).value;
    }

    function setValue(id, value) {
        document.getElementById(id).value = value;
    }
</script>
