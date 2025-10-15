@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <h2>Редагувати викладача: {{ $teacher->full_name }}</h2>

            @include('admin.teachers.editform', ['teacher' => $teacher])
        </div>
    </main>
@endsection
