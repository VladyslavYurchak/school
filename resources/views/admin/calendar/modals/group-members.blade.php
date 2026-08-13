<!-- Модальне вікно -->
<div class="modal fade" id="groupMembersModal" tabindex="-1" aria-labelledby="groupMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupMembersModalLabel">Склад групи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <ul id="groupMembersList" class="list-group mb-3" data-group-id=""></ul>

                <!-- Кнопки дій -->
                <div class="calendar-action-grid">
                    <button type="button" id="markCompletedBtn" class="btn btn-success calendar-action-primary">Проведено</button>
                    <button type="button" id="markGroupRescheduledBtn" class="btn btn-warning">Перенесено</button>
                    <button type="button" id="markCancelledBtn" class="btn btn-danger">Скасовано</button>
                </div>

                <!-- Форма відмітки присутності -->
                <div id="attendanceForm" class="mt-4 d-none">
                    <h6>Відміть, хто був присутній:</h6>
                    <form id="attendanceFormList">
                        <input type="hidden" id="lessonId" value="">
                        <input type="hidden" id="lessonDate" value="">
                        <input type="hidden" id="lessonTime" value="">

                        <div id="attendanceCheckboxes" class="list-group"></div>
                        <button type="submit" class="btn btn-primary mt-3 calendar-attendance-submit">Зберегти присутність</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- Modal для переносу -->

<div class="modal fade" id="groupRescheduleModal" tabindex="-1" aria-labelledby="groupRescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <form id="groupRescheduleForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupRescheduleModalLabel">Перенести групове заняття</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="lesson_id" id="groupRescheduleLessonId">
                <div class="mb-3">
                    <label for="groupNewDate" class="form-label">Нова дата</label>
                    <input type="date" class="form-control" name="new_date" id="groupNewDate" required>
                </div>
                <div class="mb-3">
                    <label for="groupNewTime" class="form-label">Новий час</label>
                    <input type="time" class="form-control" name="new_time" id="groupNewTime" required>
                </div>
            </div>
            <div class="modal-footer">
                <button id="submitGroupRescheduleBtn" class="btn btn-warning">Перенести</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
            </div>
        </form>
    </div>
</div>

