<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editEventForm" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editEventLabel">Редагувати час заняття</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editEventDate" class="form-label">Дата</label>
                    <input type="date" class="form-control" id="editEventDate" name="date" required>
                </div>
                <div class="mb-3">
                    <label for="editEventTime" class="form-label">Час</label>
                    <input type="time" class="form-control" id="editEventTime" name="time" required>
                </div>
                <div class="mb-3">
                    <label for="editEventDuration" class="form-label">Тривалість (хвилин)</label>
                    <input type="number" class="form-control" id="editEventDuration" name="duration" min="15" max="180" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Зберегти</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Відмінити</button>
            </div>
        </form>
    </div>
</div>
