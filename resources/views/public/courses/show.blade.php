@extends('public.layouts.main')

@section('content')
    <div class="course-show-page">

        @if(session('success'))
            <div class="container pt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="container pt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        {{-- Хедер курсу --}}
        <div class="course-hero">
            <div class="container">
                <a href="{{ route('courses.index') }}" class="course-back">← До курсів</a>
                <div class="course-hero-inner">
                    <div class="course-hero-info">
                        @if($course->language->name ?? null)
                            <span class="course-lang-badge">{{ $course->language->name }}</span>
                        @endif
                        <h1 class="course-hero-title">{{ $course->title }}</h1>
                        <p class="course-hero-desc">{{ $course->description }}</p>
                        <div class="course-hero-meta">
                            <span>📚 {{ $course->lessons->count() }} {{ trans_choice('урок|уроки|уроків', $course->lessons->count()) }}</span>
                        </div>
                    </div>

                    <div class="course-hero-card">
                        <div class="course-price">
                            @if($course->isPaid())
                                <span class="course-price-amount">{{ number_format($course->price, 0, ',', ' ') }}</span>
                                <span class="course-price-cur">грн</span>
                            @else
                                <span class="course-price-free">Безкоштовно</span>
                            @endif
                        </div>

                        @if($hasAccess)
                            <div class="course-access-badge">✓ Доступ відкрито</div>
                        @elseif($course->isPaid() && auth()->check())
                            <form action="{{ route('courses.buy', $course) }}" method="POST">
                                @csrf
                                <button type="submit" class="course-buy-btn">Придбати весь курс</button>
                            </form>
                        @elseif($course->isPaid())
                            <a href="{{ route('login') }}" class="course-buy-btn">Увійти для оплати</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Список уроків --}}
        <div class="container">
            <div class="course-lessons-section">
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
                                        Відкрити →
                                    </a>

                                @elseif($lesson->price && auth()->check())
                                    <form action="{{ route('lessons.buy', $lesson) }}" method="POST" class="mb-0">
                                        @csrf
                                        <button type="submit" class="course-lesson-btn course-lesson-btn--buy">
                                            {{ number_format($lesson->price, 0, ',', ' ') }} грн
                                        </button>
                                    </form>

                                @elseif($lesson->price && !auth()->check())
                                    <a href="{{ route('login') }}" class="course-lesson-btn course-lesson-btn--buy">
                                        {{ number_format($lesson->price, 0, ',', ' ') }} грн
                                    </a>

                                @else
                                    <span class="course-lesson-locked">🔒</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="course-lessons-empty">Уроків поки немає.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ===================== PAGE ===================== */
        .course-show-page { padding-bottom: 4rem; }
        .course-back {
            display: inline-block;
            color: rgba(255,255,255,.7);
            text-decoration: none;
            font-size: .9rem;
            margin-bottom: 1rem;
            transition: color .2s;
        }
        .course-back:hover { color: #fff; }

        /* ===================== HERO ===================== */
        .course-hero {
            background: linear-gradient(135deg, #1a1f36 0%, #2d3561 100%);
            color: #fff;
            padding: 2.5rem 0 3rem;
            margin-bottom: 2.5rem;
        }
        .course-hero-inner {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 767px) {
            .course-hero-inner { grid-template-columns: 1fr; }
        }

        .course-lang-badge {
            display: inline-block;
            background: rgba(255,255,255,.15);
            color: rgba(255,255,255,.9);
            border-radius: 20px;
            padding: .25rem .85rem;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: .85rem;
        }
        .course-hero-title {
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        .course-hero-desc {
            color: rgba(255,255,255,.75);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 1rem;
        }
        .course-hero-meta {
            display: flex;
            gap: 1.5rem;
            color: rgba(255,255,255,.6);
            font-size: .9rem;
        }

        /* ===================== HERO CARD ===================== */
        .course-hero-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.75rem;
            color: #212529;
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
        }
        .course-price {
            text-align: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #e9ecef;
        }
        .course-price-amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1f36;
            line-height: 1;
        }
        .course-price-cur {
            font-size: 1.2rem;
            color: #6c757d;
            margin-left: .25rem;
        }
        .course-price-free {
            font-size: 1.8rem;
            font-weight: 700;
            color: #198754;
        }
        .course-access-badge {
            text-align: center;
            background: #d1e7dd;
            color: #0f5132;
            border-radius: 8px;
            padding: .75rem;
            font-weight: 600;
            font-size: .95rem;
        }
        .course-buy-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .85rem 1.5rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .1s;
        }
        .course-buy-btn:hover {
            background: #0b5ed7;
            color: #fff;
            transform: translateY(-1px);
        }

        /* ===================== LESSONS ===================== */
        .course-lessons-section { max-width: 800px; }
        .course-lessons-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }

        .course-lessons-list {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
        }

        .course-lesson-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f3f5;
            background: #fafafa;
            transition: background .15s;
        }
        .course-lesson-row:last-child { border-bottom: none; }
        .course-lesson-row--open { background: #fff; }
        .course-lesson-row--open:hover { background: #f8f9fa; }

        .course-lesson-num {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            border-radius: 6px;
            background: #e9ecef;
            font-size: .8rem;
            font-weight: 700;
            color: #6c757d;
            flex-shrink: 0;
        }
        .course-lesson-row--open .course-lesson-num {
            background: #0d6efd;
            color: #fff;
        }

        .course-lesson-info { flex: 1; min-width: 0; }
        .course-lesson-name {
            font-weight: 600;
            font-size: .95rem;
            color: #212529;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .course-lesson-row:not(.course-lesson-row--open) .course-lesson-name { color: #6c757d; }
        .course-lesson-desc {
            font-size: .82rem;
            color: #adb5bd;
            margin-top: .2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .course-lesson-action { flex-shrink: 0; }
        .course-lesson-btn {
            display: inline-block;
            border-radius: 7px;
            padding: .4rem .9rem;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .15s, transform .1s;
            white-space: nowrap;
        }
        .course-lesson-btn--open {
            background: #e7f1ff;
            color: #0d6efd;
        }
        .course-lesson-btn--open:hover {
            background: #cfe2ff;
            color: #0d6efd;
            transform: translateX(2px);
        }
        .course-lesson-btn--buy {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        .course-lesson-btn--buy:hover {
            background: #ffc107;
            color: #000;
        }
        .course-lesson-locked {
            font-size: 1rem;
            color: #ced4da;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
        }

        .course-lessons-empty {
            padding: 2rem;
            text-align: center;
            color: #adb5bd;
            font-size: .95rem;
        }
    </style>

@endsection
