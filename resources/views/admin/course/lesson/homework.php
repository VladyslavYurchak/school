<!-- Домашня частина уроку: домашнє завдання -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <strong>Домашня частина уроку</strong>
    </div>
    <div class="card-body">
        <label class="form-label">
            <i class="fas fa-house-user"></i> Додати домашнє завдання:
        </label>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="has_homework" onclick="toggleHomework()">
            <label class="custom-control-label" for="has_homework">Так, додати домашнє завдання</label>
        </div>

        <div id="homework_section" style="display: none;">
            <div class="form-group mb-4">
                <label for="homework_text">
                    <i class="fas fa-book"></i> Додати домашнє завдання:
                </label>
                <textarea name="homework_text" id="homework_text" class="form-control" rows="4"></textarea>
            </div>
            <div class="form-group mb-4">
                <label for="homework_files">
                    <i class="fas fa-paperclip"></i> Додати матеріали для домашнього завдання:
                </label>
                <input type="file" name="homework_files[]" id="homework_files" class="form-control" multiple>
            </div>
            <div class="form-group mb-4">
                <label for="homework_video_url">
                    <i class="fas fa-video"></i> Додати посилання на відео для домашнього завдання:
                </label>
                <input type="url" name="homework_video_url" id="homework_video_url" class="form-control">
            </div>
        </div>
    </div>
</div>
