@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-pencil-square"></i> Редагування вправи</span>
                        <h1 class="admin-title">{{ $exercise->title }}</h1>
                    </div>
                </div>
            </section>

            @include('admin.course.lesson.exercises._form')
        </div>
    </div>
@endsection
