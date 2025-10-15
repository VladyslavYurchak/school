@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <h2 class="mb-4">Редагувати учня: {{ $student->full_name }}</h2>

            <form action="{{ route('admin.students.update', $student->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('admin.students.add_student_form', ['student' => $student])

                <div class="text-end mt-3">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Назад</a>
                </div>
            </form>
        </div>
    </main>
@endsection
