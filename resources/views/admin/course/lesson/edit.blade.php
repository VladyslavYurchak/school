@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-black text-white">
                <h4 class="form-title">
                    <i class="fas fa-edit"></i> Редагування уроку "{{ $lesson->title }}"
                </h4>
            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('admin.course.lesson.update', $lesson) }}"
                    method="POST"
                >
                    @csrf
                    @method('PUT')

                    <div class="card mb-4">
                        <div class="card-header bg-black text-white">
                            <strong>Основна інформація про урок</strong>
                        </div>

                        <div class="card-body">
                            <div class="form-group mb-4">
                                <label for="lesson_type" class="form-label">
                                    <i class="fas fa-chevron-circle-right"></i> Вид уроку:
                                </label>

                                <select id="lesson_type" name="lesson_type" class="form-control">
                                    @foreach(['Reading', 'Listening', 'Grammar', 'Speaking', 'Test'] as $type)
                                        <option
                                            value="{{ $type }}"
                                            @selected(old('lesson_type', $lesson->lesson_type) === $type)
                                        >
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading"></i> Назва уроку:
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    id="title"
                                    class="form-control"
                                    value="{{ old('title', $lesson->title) }}"
                                    required
                                >
                            </div>

                            <div class="form-group mb-4">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left"></i> Опис уроку:
                                </label>

                                <textarea
                                    name="description"
                                    id="description"
                                    class="form-control"
                                    rows="4"
                                >{{ old('description', $lesson->description) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="position" class="form-label">
                                            <i class="fas fa-list-ol"></i> Номер уроку:
                                        </label>

                                        <input
                                            type="number"
                                            name="position"
                                            id="position"
                                            class="form-control"
                                            min="0"
                                            value="{{ old('position', $lesson->position) }}"
                                        >
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="price" class="form-label">
                                            <i class="fas fa-money-bill"></i> Ціна окремого уроку:
                                        </label>

                                        <input
                                            type="number"
                                            name="price"
                                            id="price"
                                            class="form-control"
                                            min="0"
                                            step="0.01"
                                            value="{{ old('price', $lesson->price) }}"
                                            placeholder="Наприклад: 300"
                                        >

                                        <small class="text-muted">
                                            0 або порожньо — урок не продається окремо.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input
                                    type="hidden"
                                    name="is_published"
                                    value="0"
                                >

                                <input
                                    type="checkbox"
                                    name="is_published"
                                    id="is_published"
                                    value="1"
                                    class="form-check-input"
                                    @checked(old('is_published', $lesson->is_published))
                                >

                                <label for="is_published" class="form-check-label">
                                    Опублікований
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Зберегти
                    </button>

                    <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="btn btn-secondary">
                        Назад
                    </a>
                </form>
            </div>
        </div>
    </div>
@endsection
