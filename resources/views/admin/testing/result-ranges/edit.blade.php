@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="mb-0">Редагувати діапазон #{{ $resultRange->id }}</h1>
                    <div class="text-muted small">Тест: {{ $test->title }}</div>
                </div>

                <a href="{{ route('admin.testing.tests.result-ranges.index', $test) }}"
                   class="admin-btn-soft">
                    Назад
                </a>
            </div>

            <section class="admin-panel admin-form admin-form-card">
                <div class="admin-panel-body">
                    <form action="{{ route('admin.testing.result-ranges.update', $resultRange) }}" method="POST">
                        @method('PUT')
                        @include('admin.testing.result-ranges._form')
                    </form>
                </div>
            </section>

        </div>
    </div>
@endsection
