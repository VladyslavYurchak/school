@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-plus-circle"></i> Нова вправа</span>
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                    </div>
                </div>
            </section>

            @include('admin.course.lesson.exercises._form')
        </div>
    </div>
@endsection
