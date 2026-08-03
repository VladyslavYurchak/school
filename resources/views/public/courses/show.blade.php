@extends('public.layouts.main')

@section('content')
    <div class="course-show-page">
        @if(session('success'))
            <div class="container pt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container pt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
                </div>
            </div>
        @endif

        <section class="course-hero">
            <div class="container">
                <a href="{{ route('courses.index') }}" class="course-back">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    До курсів
                </a>

                <div class="course-hero-inner">
                    <div class="course-hero-info">
                        @if($course->language->name ?? null)
                            <span class="course-lang-badge">{{ $course->language->name }}</span>
                        @endif

                        <h1 class="course-hero-title">{{ $course->title }}</h1>
                        <p class="course-hero-desc">{{ $course->description }}</p>
                        <div class="course-hero-meta">
                            <span>
                                <i class="bi bi-journals" aria-hidden="true"></i>
                                {{ $course->lessons->count() }} {{ trans_choice('урок|уроки|уроків', $course->lessons->count()) }}
                            </span>
                        </div>
                    </div>

                    <aside class="course-hero-card">
                        <div class="course-price">
                            @if($course->isPaid())
                                <span class="course-price-amount">{{ number_format($course->price, 0, ',', ' ') }}</span>
                                <span class="course-price-cur">грн</span>
                            @else
                                <span class="course-price-free">Безкоштовно</span>
                            @endif
                        </div>

                        @if($hasAccess)
                            <div class="course-access-badge">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                Доступ відкрито
                            </div>
                        @elseif($course->isPaid() && auth()->check())
                            <form action="{{ route('courses.buy', $course) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="course-buy-btn"
                                        data-analytics-event="begin_checkout"
                                        data-analytics-label="course">Придбати весь курс</button>
                            </form>
                        @elseif($course->isPaid())
                            <a href="{{ route('login') }}" class="course-buy-btn">Увійти для оплати</a>
                        @endif
                    </aside>
                </div>
            </div>
        </section>

        <div class="container">
            <section class="course-lessons-section">
                <h2 class="course-lessons-title">Програма курсу</h2>

                <div class="course-lessons-list">
                    @forelse($course->lessons as $lesson)
                        @php $canOpen = $hasAccess || ($lessonAccess[$lesson->id] ?? false); @endphp

                        <div class="course-lesson-row {{ $canOpen ? 'course-lesson-row--open' : '' }}">
                            <div class="course-lesson-num">{{ $lesson->position }}</div>

                            <div class="course-lesson-info">
                                <div class="course-lesson-name">{{ $lesson->title }}</div>
                                @if($lesson->description)
                                    <div class="course-lesson-desc">{{ Str::limit($lesson->description, 100) }}</div>
                                @endif
                            </div>

                            <div class="course-lesson-action">
                                @if($canOpen)
                                    <a href="{{ route('courses.lessons.show', [$course, $lesson]) }}"
                                       class="course-lesson-btn course-lesson-btn--open">
                                        Відкрити
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </a>
                                @elseif($lesson->isPaid() && auth()->check())
                                    <form action="{{ route('lessons.buy', $lesson) }}" method="POST" class="mb-0">
                                        @csrf
                                        <button type="submit" class="course-lesson-btn course-lesson-btn--buy"
                                                data-analytics-event="begin_checkout"
                                                data-analytics-label="lesson"
                                                title="Придбати урок «{{ $lesson->title }}»"
                                                aria-label="Придбати урок «{{ $lesson->title }}» за {{ number_format($lesson->price, 0, ',', ' ') }} гривень">
                                            Придбати · {{ number_format($lesson->price, 0, ',', ' ') }} грн
                                        </button>
                                    </form>
                                @elseif($lesson->isPaid())
                                    <a href="{{ route('login') }}" class="course-lesson-btn course-lesson-btn--buy"
                                       title="Увійти, щоб придбати урок «{{ $lesson->title }}»"
                                       aria-label="Увійти, щоб придбати урок «{{ $lesson->title }}» за {{ number_format($lesson->price, 0, ',', ' ') }} гривень">
                                        Придбати · {{ number_format($lesson->price, 0, ',', ' ') }} грн
                                    </a>
                                @else
                                    <span class="course-lesson-locked" aria-label="Урок недоступний">
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="course-lessons-empty">Уроків поки немає.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
