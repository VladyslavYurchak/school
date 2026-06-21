@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="mb-0">Редагувати тест #{{ $test->id }}</h1>

                <a href="{{ route('admin.testing.tests.index') }}" class="admin-btn-soft">
                    Назад
                </a>
            </div>

            <section class="admin-panel admin-form admin-form-card">
                <div class="admin-panel-body">
                    <form action="{{ route('admin.testing.tests.update', $test) }}" method="POST">
                        @method('PUT')
                        @include('admin.testing.tests._form')
                    </form>
                </div>
            </section>

        </div>
    </div>
@endsection
