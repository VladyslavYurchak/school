@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-black text-white">
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

                <form id="lessonForm" action="{{ route('admin.course.lesson.store', ['course' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Урок: Вибір типу, назва, опис -->
                    <div class="card mb-4">
                        <div class="card-header bg-black">
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

                    <button type="submit" class="btn btn-success bg-black">
                        <i class="fas fa-check-circle"></i> Створити урок
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
