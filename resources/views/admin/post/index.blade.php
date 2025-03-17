@extends('admin.layouts.layout')

@section('content')<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="card">
        <div class="card-header">
            <a href="{{ route('admin.post.create') }}" class="btn btn-success">Створити пост</a>
        </div>
        <div class="card-body">

            <!-- Повідомлення про успіх -->
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Назва поста</th>
                    <th>Створено</th>
                    <th>Дія</th>
                </tr>
                </thead>
                <tbody>
                @foreach($posts as $post)
                    <tr>
                        <td>{{ $post->id }}</td>
                        <td><a href="{{ route('admin.post.show', $post->id) }}">{{ $post->title }}</a></td>
                        <td>{{ $post->created_at->format('d.m.Y H:i') }}</td> <!-- Форматування дати -->
                        <td>
                            <a href="{{ route('admin.post.edit', $post->id) }}" class="btn btn-warning btn-sm">Редагувати</a>
                            <!-- Форма для видалення поста -->
                            <form action="{{ route('admin.post.delete', $post->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ви впевнені, що хочете видалити цей пост?');">Видалити</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <!-- Пагінація (виносимо її нижче таблиці) -->
            <div class="d-flex justify-content-between">
                <div>
                    {{ $posts->onEachSide(2)->links('admin.pagination.pagination') }}
                </div>
            </div>

        </div>
    </div>
    <!--end::App Content-->
</main>
@endsection
