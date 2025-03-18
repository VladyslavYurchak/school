@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="form-title">
                    <i class="fas fa-book-open"></i> Створення уроку до курсу "{{ $course->name }}"
                </h4>
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

                <form id="lessonForm" action="{{ route('admin.lesson.store', ['course' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Урок: Вибір типу, назва, опис -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <strong>Основна інформація про урок</strong>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-4">
                                <label for="lesson_type" class="form-label">
                                    <i class="fas fa-chevron-circle-right"></i> Оберіть вид уроку:
                                </label>
                                <select id="lesson_type" name="lesson_type" class="form-control select2">
                                    <option value="Reading">📖 Reading</option>
                                    <option value="Listening">🎧 Listening</option>
                                    <option value="Grammar">📝 Grammar</option>
                                    <option value="Speaking">🗣️ Speaking</option>
                                    <option value="Test">✅ Test</option>
                                </select>
                            </div>
                            <div class="form-group mb-4">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading"></i> Введіть назву уроку:
                                </label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left"></i> Опишіть даний урок:
                                </label>
                                <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Основна частина уроку: завдання, матеріали, аудіо та відео -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <strong>Основна частина уроку</strong>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-4">
                                <label for="content" class="form-label">
                                    <i class="fas fa-tasks"></i> Напишіть основне завдання уроку:
                                </label>
                                <textarea name="content" id="content" class="form-control" rows="6"></textarea>
                            </div>
                            <div class="form-group mb-4">
                                <label for="media_files" class="form-label">
                                    <i class="fas fa-paperclip"></i> Додайте матеріали для уроку:
                                </label>
                                <input type="file" name="media_files[]" id="media_files" class="form-control" multiple>
                            </div>
                            <div class="form-group mb-4">
                                <label for="audio_file" class="form-label">
                                    <i class="fas fa-microphone"></i> Додайте аудіофайл:
                                </label>
                                <input type="file" name="audio_file" id="audio_file" class="form-control">
                            </div>
                            <div class="form-group mb-4">
                                <label for="video_url" class="form-label">
                                    <i class="fas fa-video"></i> Додайте посилання на відео:
                                </label>
                                <input type="url" name="video_url" id="video_url" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Контрольна частина уроку: тести -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <strong>Контрольна частина уроку</strong>
                        </div>
                        <div class="card-body">
                            <label class="form-label">
                                <i class="fas fa-tasks"></i> Додати тестові завдання:
                            </label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="has_tests" onclick="toggleTests()">
                                <label class="custom-control-label" for="has_tests">Так, додати тести</label>
                            </div>

                            <div id="test_section" style="display: none;">
                                <div class="form-group mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-question-circle"></i> Додати питання та варіанти відповідей:
                                    </label>
                                    <div id="testsContainer">
                                        <!-- Перший тест -->
                                        <div class="test" data-test-index="0">
                                            <div class="form-group mb-2">
                                                <label for="tests[0][question]">Питання:</label>
                                                <input type="text" name="tests[0][question]" class="form-control" required />
                                            </div>
                                            <div class="form-group mb-2">
                                                <label>Варіанти відповідей:</label>
                                                <div class="answer-options">
                                                    <div class="input-group mb-2">
                                                        <input type="text" name="tests[0][answers][0]" class="form-control" placeholder="Варіант 1" required />
                                                        <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <input type="text" name="tests[0][answers][1]" class="form-control" placeholder="Варіант 2" required />
                                                        <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <input type="text" name="tests[0][answers][2]" class="form-control" placeholder="Варіант 3" required />
                                                        <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                                                    </div>
                                                    <div class="input-group mb-2" style="display: none;">
                                                        <input type="text" name="tests[0][answers][3]" class="form-control" placeholder="Варіант 4" />
                                                        <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                                                    </div>
                                                    <div class="input-group mb-2" style="display: none;">
                                                        <input type="text" name="tests[0][answers][4]" class="form-control" placeholder="Варіант 5" />
                                                        <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-success btn-sm mt-2" onclick="addAnswer(this)">+</button>
                                            </div>
                                            <div class="form-group mb-2">
                                                <label for="tests[0][correct_answer]">Правильна відповідь:</label>
                                                <select name="tests[0][correct_answer]" class="form-control" required>
                                                    <option value="0">1</option>
                                                    <option value="1">2</option>
                                                    <option value="2">3</option>
                                                    <option value="3">4</option>
                                                    <option value="4">5</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="addTestButton" class="btn btn-secondary mt-2">Додати ще питання</button>
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Створити урок
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            $('#lesson_type').select2();
        });

        function toggleTests() {
            const section = document.getElementById('test_section');
            section.style.display = document.getElementById('has_tests').checked ? 'block' : 'none';
        }

        function toggleHomework() {
            const section = document.getElementById('homework_section');
            section.style.display = document.getElementById('has_homework').checked ? 'block' : 'none';
        }

        // Додавання варіанту відповіді (максимум 5, мінімум 3)
        function addAnswer(button) {
            var formGroup = button.closest('.form-group');
            var answerOptions = formGroup.querySelector('.answer-options');
            var visibleGroups = answerOptions.querySelectorAll('.input-group:not([style*="display: none"])');
            var currentCount = visibleGroups.length;
            if (currentCount < 5) {
                // Отримання індексу тесту для коректного формування name атрибуту
                var testBlock = button.closest('.test');
                var testIndex = testBlock.getAttribute('data-test-index');
                var newInputGroup = document.createElement('div');
                newInputGroup.className = "input-group mb-2";
                newInputGroup.innerHTML = '<input type="text" name="tests['+testIndex+'][answers]['+currentCount+']" class="form-control" placeholder="Варіант '+ (currentCount+1) +'" />' +
                    '<button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>';
                answerOptions.appendChild(newInputGroup);
            }
        }

        // Видалення варіанту відповіді (якщо більше 3)
        function removeAnswer(button) {
            var answerOptions = button.closest('.answer-options');
            var visibleGroups = answerOptions.querySelectorAll('.input-group:not([style*="display: none"])');
            if (visibleGroups.length > 3) {
                button.closest('.input-group').remove();
            } else {
                alert('Мінімум 3 варіанти відповіді обов\'язкові.');
            }
        }

        // Додавання нового тестового блоку
        let testCounter = 1;
        document.getElementById('addTestButton').addEventListener('click', function() {
            const newTest = document.createElement('div');
            newTest.classList.add('test');
            newTest.setAttribute('data-test-index', testCounter);
            newTest.id = `test-${testCounter}`;
            newTest.innerHTML = `
                <div class="form-group mb-2">
                    <label for="tests[${testCounter}][question]">Питання:</label>
                    <input type="text" name="tests[${testCounter}][question]" class="form-control" required />
                </div>
                <div class="form-group mb-2">
                    <label>Варіанти відповідей:</label>
                    <div class="answer-options">
                        <div class="input-group mb-2">
                            <input type="text" name="tests[${testCounter}][answers][0]" class="form-control" placeholder="Варіант 1" required />
                            <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                        </div>
                        <div class="input-group mb-2">
                            <input type="text" name="tests[${testCounter}][answers][1]" class="form-control" placeholder="Варіант 2" required />
                            <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                        </div>
                        <div class="input-group mb-2">
                            <input type="text" name="tests[${testCounter}][answers][2]" class="form-control" placeholder="Варіант 3" required />
                            <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                        </div>
                        <div class="input-group mb-2" style="display: none;">
                            <input type="text" name="tests[${testCounter}][answers][3]" class="form-control" placeholder="Варіант 4" />
                            <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                        </div>
                        <div class="input-group mb-2" style="display: none;">
                            <input type="text" name="tests[${testCounter}][answers][4]" class="form-control" placeholder="Варіант 5" />
                            <button type="button" class="btn btn-danger btn-sm remove-answer" onclick="removeAnswer(this)">-</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-sm mt-2" onclick="addAnswer(this)">+</button>
                </div>
                <div class="form-group mb-2">
                    <label for="tests[${testCounter}][correct_answer]">Правильна відповідь:</label>
                    <select name="tests[${testCounter}][correct_answer]" class="form-control" required>
                        <option value="0">Варіант 1</option>
                        <option value="1">Варіант 2</option>
                        <option value="2">Варіант 3</option>
                        <option value="3">Варіант 4</option>
                        <option value="4">Варіант 5</option>
                    </select>
                </div>
            `;
            document.getElementById('testsContainer').appendChild(newTest);
            testCounter++;
        });

        // Перевірка кожного тесту на наявність хоча б 3 заповнених варіантів
        document.getElementById('lessonForm').addEventListener('submit', function(event) {
            const tests = document.querySelectorAll('#testsContainer .test');
            for (let test of tests) {
                const answerInputs = test.querySelectorAll('input[name*="[answers]"]');
                let filledCount = 0;
                answerInputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        filledCount++;
                    }
                });
                if (filledCount < 3) {
                    alert("Будь ласка, введіть хоча б три варіанти відповіді для кожного тесту.");
                    event.preventDefault();
                    return;
                }
            }
        });
    </script>
@endsection
