<div class="modal fade" id="manageEventModal" tabindex="-1" aria-labelledby="manageEventLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
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
            <div class="modal-footer calendar-action-grid">
                <button type="button" class="btn btn-success calendar-action-primary" id="markAsCompleted">Проведене</button>
                <button type="button" class="btn btn-warning" id="markAsRescheduled">Перенесене</button>
                <button type="button" class="btn btn-danger" id="markAsCancelled">Скасоване</button>
                <button type="button" class="btn btn-outline-danger d-none calendar-action-wide" id="cancelStudentFutureLessons">
                    Скасувати це та всі майбутні заняття учня
                </button>
                <button type="button" class="btn btn-outline-primary" id="editEvent">Редагувати</button>
            </div>
        </div>
    </div>
</div>
