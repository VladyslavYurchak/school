@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-plus-lg"></i>
                            Тестування
                        </span>
                        <h1 class="admin-title">Створити тест</h1>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.testing.tests.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            Назад
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel admin-form admin-form-card">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані тесту</h2>
                </div>

                <div class="admin-panel-body">
                    <form action="{{ route('admin.testing.tests.store') }}" method="POST">
                        @include('admin.testing.tests._form')
                    </form>
                </div>
            </section>

        </div>
    </div>
@endsection
