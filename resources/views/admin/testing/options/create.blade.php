@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="mb-0">Додати варіант відповіді</h1>
                    <div class="text-muted small">Питання #{{ $question->id }}</div>
                </div>

                <a href="{{ route('admin.testing.questions.options.index', $question) }}"
                   class="btn btn-outline-secondary">
                    Назад
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.testing.questions.options.store', $question) }}" method="POST">
                        @include('admin.testing.options._form')
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
