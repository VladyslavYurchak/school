@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-person-badge"></i>
                            Викладачі
                        </span>
                        <h1 class="admin-title">Редагувати викладача: {{ $teacher->full_name }}</h1>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('teachers.index') }}#teacher-{{ $teacher->id }}" class="admin-btn-soft" target="_blank" rel="noopener">
                            <i class="bi bi-eye"></i>
                            Переглянути на сайті
                        </a>
                        <a href="{{ route('admin.teachers.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До викладачів
                        </a>
                    </div>
                </div>
            </section>

            @include('admin.teachers.editform', ['teacher' => $teacher])
        </div>
    </div>
@endsection
