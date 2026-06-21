@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="mb-0">Додати варіант відповіді</h1>
                    <div class="text-muted small">Питання #{{ $question->id }}</div>
                </div>

                <a href="{{ route('admin.testing.questions.options.index', $question) }}"
                   class="admin-btn-soft">
                    Назад
                </a>
            </div>

            <section class="admin-panel admin-form admin-form-card">
                <div class="admin-panel-body">
                    <form action="{{ route('admin.testing.questions.options.store', $question) }}" method="POST">
                        @include('admin.testing.options._form')
                    </form>
                </div>
            </section>

        </div>
    </div>
@endsection
