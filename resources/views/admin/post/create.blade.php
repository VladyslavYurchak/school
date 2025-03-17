@extends('admin.layouts.layout')

@section('content')<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="card">
        <div class="card-header">
            <a href="{{ route('admin.post.index') }}" class="btn btn-secondary">Повернутися до списку постів</a>
        </div>
        <div class="card-body">
            <h3>Створити пост</h3>
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Форма для створення поста -->
            <form action="{{ route('admin.post.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Заголовок</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Введіть заголовок поста" required>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Зміст</label>
                    <textarea name="content" id="content" class="form-control" rows="5" placeholder="Введіть зміст поста" required></textarea>
                </div>
                <div class="form-group mb-3">
                    <label for="image" class="form-label" style="font-weight: bold;">Фото URL</label>
                    <input type="text" class="form-control border-dark" name="image" id="image"
                           placeholder="Вставте URL фото"
                           style="background-color: #f8f9fa; color: #000; border-radius: 8px;">
                    <small class="text-danger">
                        @error('image')
                        {{ $message }}
                        @enderror
                    </small>
                </div>

                <button type="submit" class="btn btn-success">Створити пост</button>
            </form>
        </div>
    </div>
    <!--end::App Content-->
</main>
@endsection
