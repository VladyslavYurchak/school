@extends('admin.layouts.layout')

@section('content')<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="card">
        <div class="card-header">
            <a href="{{ route('admin.event.index') }}" class="btn btn-secondary">Повернутися до списку подій</a>
        </div>
        <div class="card-body">
            <h3>Створити подію</h3>
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Форма для створення події -->
            <form action="{{ route('admin.event.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Назва події</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Введіть заголовок події" required>
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

                <div class="form-group mb-3">
                    <label for="start_date" class="form-label">Вкажіть дату</label>
                    <input type="date" class="form-control" name="start_date" id="start_date" required>
                    <small class="text-danger">
                        @error('start_date')
                        {{ $message }}
                        @enderror
                    </small>
                </div>

                <button type="submit" class="btn btn-success">Створити подію</button>
            </form>
        </div>
    </div>
    <!--end::App Content-->
</main>
@endsection
