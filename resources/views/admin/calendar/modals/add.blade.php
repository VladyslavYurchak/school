<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addEventForm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addEventLabel">Додати нове заняття</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="eventDate" class="form-label">Дата</label>
                        <input type="date" class="form-control" id="eventDate" name="date" required>
                    </div>

                    <div class="mb-3">
                        <label for="eventTime" class="form-label">Час</label>
                        <input type="time" class="form-control" id="eventTime" name="time" value="09:00" required>
                    </div>

                    <div class="mb-3">
                        <label for="eventDuration" class="form-label">Тривалість (хвилин)</label>
                        <input type="number" class="form-control" id="eventDuration" name="duration" value="60" min="15" max="180" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Тип заняття</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="lesson_type" id="lessonTypeIndividual" value="individual" checked>
                                <label class="form-check-label" for="lessonTypeIndividual">Індивідуальне</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="lesson_type" id="lessonTypeGroup" value="group">
                                <label class="form-check-label" for="lessonTypeGroup">Групове</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="lesson_type" id="lessonTypePair" value="pair">
                                <label class="form-check-label" for="lessonTypePair">Парне</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="lesson_type" id="lessonTypeTrial" value="trial">
                                <label class="form-check-label" for="lessonTypeTrial">Пробне</label>
                            </div>
                        </div>
                    </div>


                    <div class="mb-3" id="studentSelectContainer">
                        <label for="eventStudent" class="form-label">Студент</label>
                        <select class="form-select" id="eventStudent" name="student_id">
                            <option value="" disabled selected>Оберіть студента</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->last_name }} {{ $student->first_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="groupSelectContainer">
                        <label for="eventGroup" class="form-label">Група</label>
                        <select class="form-select" id="eventGroup" name="group_id">
                            <option value="" disabled selected>Оберіть групу</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div class="mb-3">
                        <label for="eventNotes" class="form-label">Нотатки</label>
                        <textarea class="form-control" id="eventNotes" name="notes" rows="2"></textarea>
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="repeatWeekly" name="repeat_weekly">
                        <label class="form-check-label" for="repeatWeekly">
                            Повторювати щотижня до кінця місяця
                        </label>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Відміна</button>
                    <button type="submit" class="btn btn-primary">Додати заняття</button>
                </div>
            </div>
        </form>
    </div>
</div>
