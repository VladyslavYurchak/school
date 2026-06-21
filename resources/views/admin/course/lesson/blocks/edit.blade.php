@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-pencil-square"></i> Редагування блока</span>
                        <h1 class="admin-title">{{ $block->title ?: $lesson->title }}</h1>
                    </div>
                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.blocks.index', $lesson) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i> До конструктора
                        </a>
                    </div>
                </div>
            </section>

            @include('admin.course.lesson.blocks._form', ['type' => $block->type])
        </div>
    </div>
@endsection
