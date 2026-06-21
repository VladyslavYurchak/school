@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-mortarboard"></i>
                            Учень
                        </span>
                        <h1 class="admin-title">Редагувати учня</h1>
                        <p class="admin-subtitle">{{ $student->full_name }}</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.students.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані учня</h2>
                </div>

                <div class="admin-panel-body admin-form">
                    @include('admin.students.add_student_form', ['student' => $student])
                </div>
            </section>
        </div>
    </div>
@endsection
