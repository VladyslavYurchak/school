@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-pencil-square"></i> Редагування слова</span>
                        <h1 class="admin-title">{{ $link->vocabularyItem->term }}</h1>
                    </div>
                </div>
            </section>
            @include('admin.course.lesson.vocabulary._form')
        </div>
    </div>
@endsection
