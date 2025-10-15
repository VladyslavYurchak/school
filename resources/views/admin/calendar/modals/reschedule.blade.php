<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="rescheduleForm" class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="rescheduleLabel">Перенесення заняття</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newDate" class="form-label">Нова дата</label>
                    <input type="date" class="form-control" id="newDate" name="new_date" required>
                </div>
                <div class="mb-3">
                    <label for="newTime" class="form-label">Новий час</label>
                    <input type="time" class="form-control" id="newTime" name="new_time" required>
                </div>
                <div class="mb-3">
                    <label for="initiator" class="form-label">Хто переносить</label>
                    <select class="form-select" id="initiator" name="initiator" required>
                        <option value="student">Учень</option>
                        <option value="teacher">Викладач</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Підтвердити перенесення</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Відмінити</button>
            </div>
        </form>
    </div>
</div>
