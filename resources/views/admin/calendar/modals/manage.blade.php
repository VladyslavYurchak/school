<div class="modal fade" id="manageEventModal" tabindex="-1" aria-labelledby="manageEventLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="manageEventLabel">Управління заняттям</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <p><strong>Назва:</strong> <span id="manageEventTitle"></span></p>
                <p><strong>Дата:</strong> <span id="manageEventDate"></span></p>
                <p><strong>Час:</strong> <span id="manageEventTime"></span></p>
            </div>
            <div class="modal-footer d-flex justify-content-between flex-wrap gap-2">
                <button class="btn btn-success" id="markAsCompleted">Проведене</button>
                <button class="btn btn-warning" id="markAsRescheduled">Перенесене</button>
                <button class="btn btn-danger" id="markAsCancelled">Скасоване</button>
                <button class="btn btn-outline-primary" id="editEvent">Редагувати</button>
            </div>
        </div>
    </div>
</div>
