@extends('admin.layouts.layout')

@section('content')<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="card">
        <div class="card-header">
            <a href="{{route('admin.event.create')}}" class="btn btn-success">Створити подію</a>
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
                    <th>Назва події</th>
                    <th>Дата події</th> <!-- Додана колонка "Дата події" -->
                    <th>Створено</th>
                    <th>Дія</th>
                </tr>
                </thead>
                <tbody>
                @foreach($events as $event)
                    <tr>
                        <td>{{ $event->id }}</td>
                        <td><a href="#">{{ $event->title }}</a></td>
                        <td>{{ $event->start_date->format('d.m.Y') }}</td> <!-- Дата події -->
                        <td>{{ $event->created_at->format('d.m.Y H:i') }}</td> <!-- Форматування дати створення -->
                        <td>
                            <a href="#" class="btn btn-warning btn-sm">Редагувати</a>
                            <!-- Форма для видалення події -->
                            <form action="{{route('admin.event.delete', $event->id)}}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ви впевнені, що хочете видалити цю подію?');">Видалити</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <!-- Пагінація (виносимо її нижче таблиці) -->
            <div class="d-flex justify-content-between">
                <div>
                    {{ $events->onEachSide(2)->links('admin.pagination.pagination') }}
                </div>
            </div>

        </div>
    </div>
    <!--end::App Content-->
</main>
@endsection
