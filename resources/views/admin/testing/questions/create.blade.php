@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="mb-0">Створити питання</h1>
                    <div class="text-muted small">Тест: {{ $test->title }}</div>
                </div>

                <a href="{{ route('admin.testing.tests.questions.index', $test) }}"
                   class="btn btn-outline-secondary">
                    Назад
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.testing.tests.questions.store', $test) }}" method="POST">
                        @include('admin.testing.questions._form')
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
