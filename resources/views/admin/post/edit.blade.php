@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('admin.post.index') }}" class="btn btn-secondary">Повернутися до списку постів</a>
            </div>
            <div class="card-body">
                <h3>Редагувати пост</h3>

                <form action="{{ route('admin.post.update', $post->id) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="title" class="form-label">Заголовок</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $post->title) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Зміст</label>
                        <textarea name="content" id="content" class="form-control" rows="5" required>{{ old('content', $post->content) }}</textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="image" class="form-label">Фото URL</label>
                        <input type="text" class="form-control" name="image" id="image" value="{{ old('image', $post->image) }}" placeholder="Вставте URL фото">
                    </div>

                    <button type="submit" class="btn btn-success">Оновити пост</button>
                </form>
            </div>
        </div>
    </main>
@endsection
