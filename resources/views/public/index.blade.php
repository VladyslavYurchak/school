@extends('public.layouts.main')

@section('content')
    <div class="home-page py-5">
        <div class="container">

            {{-- HERO --}}
            <section class="hero-section mb-5">
                <div class="hero-card">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-7">
                            <span class="hero-badge">Безкоштовне визначення Вашого рівня мови</span>
                            <h1 class="hero-title">
                                Вивчайте іноземні мови сучасно, впевнено та з підтримкою викладача
                            </h1>
                            <p class="hero-text">
                                Пройдіть безкоштовне тестування, дізнайтесь свій рівень та оберіть формат навчання,
                                який підходить саме вам.
                            </p>

                            <div class="hero-actions">
                                <a href="#testing-block" class="btn btn-brand">
                                    Пройти тестування
                                </a>
                                <a href="#events-block" class="btn btn-light-brand">
                                    Переглянути події
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="hero-side-card">
                                <h3 class="mini-title mb-3">Чому обирають нас</h3>
                                <ul class="feature-list">
                                    <li>Живі заняття з викладачем</li>
                                    <li>Сучасні матеріали та практика</li>
                                    <li>Групові та індивідуальні формати</li>
                                    <li>Безкоштовне визначення рівня</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="self-study-section section-space">
                <div class="self-study-card">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-7">
                            <span class="hero-badge">Онлайн-уроки</span>
                            <h2 class="section-title mb-3">Навчайтесь онлайн без викладача</h2>
                            <p class="section-text mb-4">
                                Отримайте доступ до уроків різних рівнів і вдосконалюйте англійську
                                самостійно, у зручний для вас час.
                            </p>

                            <div class="hero-actions">
                                <a href="#" class="btn btn-brand">Переглянути уроки</a>
                                <a href="#" class="btn btn-brand-outline">Дізнатись більше</a>
                            </div>

                            <p class="levels-text mt-3">
                                Доступні безкоштовні уроки для всіх рівнів: <strong>A1–C2</strong>
                            </p>

                        </div>

                        <div class="col-lg-5 text-center">
                            <div class="self-study-image-wrap">
                                <img src="{{ asset('images/self-study-bag.png') }}" alt="Онлайн-уроки" class="self-study-image">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- MAIN GRID --}}
            <div class="row g-4">

                {{-- PHOTOS --}}
                <div class="col-lg-6">
                    <section class="content-card h-100">
                        <div class="section-head">
                            <h2 class="section-title mb-0">Наші фото</h2>
                        </div>

                        <div class="photo-gallery-wrapper">
                            <div class="photo-gallery">
                                @foreach ($photos as $photo)
                                    <div class="photo-slide">
                                        <img src="{{ asset('storage/' . $photo->path) }}" alt="Фото" class="img-fluid gallery-image">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                </div>

                {{-- EVENTS --}}
                <div class="col-lg-6" id="events-block">
                    <section class="content-card h-100">
                        <div class="section-head">
                            <h2 class="section-title mb-0">Заплановані події</h2>
                        </div>

                        <div class="events-list">
                            @forelse ($events as $event)
                                <article class="event-card">
                                    <div class="event-date-box">
                                        {{ \Carbon\Carbon::parse($event->start_date)->format('d.m.Y') }}
                                    </div>

                                    <div class="event-content">
                                        <h3 class="event-title mb-2">{{ $event->title }}</h3>

                                        @if ($event->image)
                                            <div class="event-image-wrap mt-3">
                                                <img
                                                    src="{{ asset('storage/' . $event->image) }}"
                                                    alt="{{ $event->title }}"
                                                    class="event-image-preview"
                                                >
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <p class="empty-text mb-0">Наразі запланованих подій немає.</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                {{-- TESTING --}}
                <div class="col-lg-6" id="testing-block">
                    <section class="content-card highlight-card h-100">
                        <div class="section-head">
                            <h2 class="section-title mb-0">Безкоштовне тестування</h2>
                        </div>

                        <p class="section-text">
                            Перевірте свої знання іноземної мови та дізнайтесь свій орієнтовний рівень просто зараз.
                        </p>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <form action="{{ route('testing.start', 'en') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-brand w-100">Англійська</button>
                                </form>
                            </div>

                            <div class="col-md-4">
                                <form action="{{ route('testing.start', 'fr') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-brand w-100">Французька</button>
                                </form>
                            </div>

                            <div class="col-md-4">
                                <form action="{{ route('testing.start', 'zh') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-brand w-100">Китайська</button>
                                </form>
                            </div>
                        </div>

                        <div class="info-links mt-4">
                            <a href="#" class="info-link">
                                Записатись на безкоштовне групове заняття
                            </a>

                            <a href="#" class="info-link">
                                Отримати безкоштовний доступ до уроків, відеоматеріалів та спільноти
                            </a>
                        </div>
                    </section>
                </div>

                {{-- POSTS --}}
                <div class="col-lg-6">
                    <section class="content-card h-100">
                        <div class="section-head">
                            <h2 class="section-title mb-0">Останні пости</h2>
                        </div>

                        <div class="posts-list">
                            @forelse ($posts as $post)
                                <a href="{{ route('posts.show', $post->id) }}" class="post-item">
                                    <span class="post-number">{{ $loop->iteration }}</span>
                                    <span class="post-title">{{ $post->title }}</span>
                                </a>
                            @empty
                                <p class="empty-text mb-0">Постів поки немає.</p>
                            @endforelse
                        </div>

                        <div class="mt-4 d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm custom-pagination mb-0">
                                    @if ($posts->lastPage() > 1)
                                        <li class="page-item {{ ($posts->currentPage() == 1) ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $posts->url(1) }}">1</a>
                                        </li>

                                        @if ($posts->currentPage() > 3)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif

                                        @if ($posts->currentPage() > 2)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $posts->url($posts->currentPage() - 1) }}">
                                                    {{ $posts->currentPage() - 1 }}
                                                </a>
                                            </li>
                                        @endif

                                        @if ($posts->currentPage() > 1 && $posts->currentPage() < $posts->lastPage())
                                            <li class="page-item active">
                                                <span class="page-link">{{ $posts->currentPage() }}</span>
                                            </li>
                                        @endif

                                        @if ($posts->currentPage() < $posts->lastPage() - 1)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $posts->url($posts->currentPage() + 1) }}">
                                                    {{ $posts->currentPage() + 1 }}
                                                </a>
                                            </li>
                                        @endif

                                        @if ($posts->currentPage() < $posts->lastPage() - 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif

                                        <li class="page-item {{ ($posts->currentPage() == $posts->lastPage()) ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $posts->url($posts->lastPage()) }}">
                                                {{ $posts->lastPage() }}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>


    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css"/>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>

    <script>
        $(document).ready(function () {
            $('.photo-gallery').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: true,
                dots: true,
                infinite: true,
                autoplay: true,
                autoplaySpeed: 3000,
                adaptiveHeight: false
            });
        });
    </script>
@endsection
