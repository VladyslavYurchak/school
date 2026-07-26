@extends('public.layouts.main')

@section('content')
    <div class="catalog-page">
        <div class="container">
            <header class="catalog-header">
                <div>
                    <span class="catalog-eyebrow">
                        <i class="bi bi-mortarboard" aria-hidden="true"></i>
                        Онлайн-навчання
                    </span>
                    <h1 class="catalog-title">Курси та уроки</h1>
                    <p class="catalog-subtitle">Оберіть програму та навчайтесь у власному темпі. Безкоштовні матеріали відкриваються одразу.</p>
                </div>
            </header>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row g-4">
                @forelse($courses as $course)
                    <div class="col-md-6 col-xl-4">
                        <article class="catalog-card">
                            <div class="catalog-card-header">
                                <h2 class="catalog-card-title">{{ $course->title }}</h2>
                                @php
                                    $hasAccess = $course->isAvailableFor(auth()->user());
                                @endphp

                                <span class="catalog-status catalog-status--{{ $hasAccess ? 'open' : ($course->isPaid() ? 'paid' : 'free') }}">
                                    @if($hasAccess)
                                        Доступ відкрито
                                    @elseif($course->isPaid())
                                        {{ number_format($course->price, 0, ',', ' ') }} грн
                                    @else
                                        Безкоштовно
                                    @endif
                                </span>
                            </div>

                            <div class="catalog-card-language">{{ $course->language->name ?? '' }}</div>
                            <p class="catalog-card-description">{{ \Illuminate\Support\Str::limit($course->description, 140) }}</p>
                            <footer class="catalog-card-footer">
                                <span class="catalog-card-meta">Уроків: {{ $course->lessons_count }}</span>
                                <a href="{{ route('courses.show', $course) }}" class="btn-brand">Переглянути</a>
                            </footer>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="catalog-empty">Поки немає опублікованих курсів.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $courses->links() }}
            </div>
        </div>
    </div>
@endsection
