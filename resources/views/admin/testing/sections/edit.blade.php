@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="mb-0">Редагувати секцію #{{ $section->id }}</h1>
                    <div class="text-muted small">Тест: {{ $test->title }}</div>
                </div>

                <a href="{{ route('admin.testing.tests.sections.index', $test) }}"
                   class="admin-btn-soft">
                    Назад
                </a>
            </div>

            <section class="admin-panel admin-form admin-form-card">
                <div class="admin-panel-body">
                    <form action="{{ route('admin.testing.sections.update', $section) }}" method="POST" enctype="multipart/form-data">
                        @method('PUT')
                        @include('admin.testing.sections._form')
                    </form>
                </div>
            </section>

        </div>
    </div>
@endsection
