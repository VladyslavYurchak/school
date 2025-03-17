@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <!-- Карта -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="form-title"><i class="fas fa-book-open"></i> Створення уроку</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('admin.lesson.store', ['course' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Вибір виду уроку -->
                    <div class="form-group mb-5">
                        <label for="lesson_type" class="form-label"><i class="fas fa-chevron-circle-right"></i> Вкажіть вид даного уроку:</label>
                        <select id="lesson_type" name="lesson_type" class="form-control select2">
                            <option value="Reading">📖 Reading</option>
                            <option value="Listening">🎧 Listening</option>
                            <option value="Grammar">📝 Grammar</option>
                            <option value="Speaking">🗣️ Speaking</option>
                            <option value="Test">✅ Test</option>
                        </select>
                    </div>

                    <div class ="form-group mb-5">
                        <label for="title" class="form-label"><i class="fas fa-heading"></i> Назва уроку:</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="form-group mb-5">
                        <label for="content" class="form-label"><i class="fas fa-align-left"></i> Основний зміст уроку:</label>
                        <textarea name="content" id="content" class="form-control" rows="6"></textarea>
                    </div>

                    <!-- Поле для відеоуроку -->
                    <div class="form-group mb-5">
                        <label for="video_url" class="form-label"><i class="fas fa-video"></i> Посилання на відео уроку (YouTube):</label>
                        <input type="url" name="video_url" id="video_url" class="form-control">
                    </div>

                    <!-- Горизонтальна полоса -->
                    <hr class="my-4" id="lesson_type_divider" style="display: none;">

                    <!-- Тести та коментування -->
                    <div class="form-group mb-5" id="lesson_block_3">
                        <!-- Блок тестових питань -->
                        <div id="test_questions" style="display: none;">
                            <h5 class="section-title"><i class="fas fa-tasks"></i> Тестові питання</h5>
                            <div id="questions_container"></div>
                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="addTestQuestion()">
                                <i class="fas fa-plus-circle"></i> Додати питання
                            </button>
                        </div>
                        <!-- Блок коментарю -->
                        <div id="lesson_comment" style="display: none;">
                            <label for="lesson_comment_field" class="form-label"><i class="fas fa-comments"></i> Коментар до уроку:</label>
                            <textarea name="lesson_comment" id="lesson_comment_field" class="form-control" rows="4"></textarea>
                        </div>
                    </div>

                    <!-- Медіа файли -->
                    <div class="form-group mb-5">
                        <label class="form-label"><i class="fas fa-images"></i> Бажаєте ви додати файли?</label>
                        <div>
                            <label>
                                <input type="radio" name="add_media" value="yes" onclick="toggleMediaUpload(true)"> Так
                            </label>
                            <label class="ml-3">
                                <input type="radio" name="add_media" value="no" onclick="toggleMediaUpload(false)" checked> Ні
                            </label>
                        </div>
                        <div id="media_upload" style="display: none;">
                            <label for="media_files"><i class="fas fa-paperclip"></i> Прикріпити файли:</label>
                            <input type="file" name="media_files[]" id="media_files" class="form-control mb-2" multiple>
                        </div>
                    </div>

                    <!-- Горизонтальна полоса -->
                    <hr class="my-4">

                    <!-- Домашнє завдання -->
                    <div class="form-group mb-5">
                        <label class="form-label"><i class="fas fa-house-user"></i> Чи бажаєте ви додати домашнє завдання?</label>
                        <div>
                            <label>
                                <input type="radio" name="has_homework" value="yes" onclick="toggleHomework(true)"> Так
                            </label>
                            <label class="ml-3">
                                <input type="radio" name="has_homework" value="no" onclick="toggleHomework(false)" checked> Ні
                            </label>
                        </div>
                        <div id="homework_section" style="display: none;">
                            <label for="homework_text">Домашнє завдання:</label>
                            <textarea name="homework_text" id="homework_text" class="form-control mb-2" rows="4"></textarea>
                            <label for="homework_file">Прикріпити файл:</label>
                            <input type="file" name="homework_file[]" id="homework_file" class="form-control mb-2" multiple>
                            <!-- Поле для відео домашнього завдання -->
                            <label for="homework_video_url"><i class="fas fa-video"></i> Посилання на відео для домашнього завдання:</label>
                            <input type="url" name="homework_video_url" id="homework_video_url" class="form-control mb-2">
                        </div>
                    </div>

                    <!-- Кнопка створення -->
                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Створити урок</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Ініціалізація select2
            $(document).ready(function() {
                $('#lesson_type').select2();
            });
        });

        // Показуємо/ховаємо медіаблок
        function toggleMediaUpload(show) {
            document.getElementById('media_upload').style.display = show ? 'block' : 'none';
        }

        function toggleHomework(show) {
            const section = document.getElementById('homework_section');
            section.style.display = show ? 'block' : 'none';

            if (!show) {
                document.getElementById('homework_text').value = '';
                document.getElementById('homework_file').value = '';
                document.getElementById('homework_video_url').value = '';
            }
        }
    </script>
    <style>
        .lesson-link {
            color: #007bff;
            text-decoration: underline;
        }

        .lesson-link:hover {
            color: #0056b3;
            text-decoration: none;
        }
    </style>
@endsection
