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
                                <a href="{{ route('courses.index') }}" class="btn btn-brand">Переглянути уроки</a>
                                <a href="#testing-block" class="btn btn-brand-outline">Дізнатись більше</a>
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
                <div class="col-lg-6" id="posts-block">
                    <section class="content-card h-100">
                        <div class="section-head">
                            <h2 class="section-title mb-0">Наші фото</h2>
                        </div>

                        <div class="photo-gallery-wrapper">
                            @if($photos->isNotEmpty())
                                <div class="photo-gallery">
                                    @foreach ($photos as $photo)
                                        <div class="photo-slide">
                                            <img src="{{ asset('storage/' . $photo->path) }}" alt="Фото школи" class="img-fluid gallery-image">
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-camera"></i></div>
                                    <h3 class="empty-state-title">Фото скоро зʼявляться</h3>
                                    <p class="empty-text mb-0">Додайте фото в адмін-панелі, і вони автоматично зʼявляться тут.</p>
                                </div>
                            @endif
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
                                    <div class="event-media-column">
                                        <div class="event-date-box">
                                            <span>{{ $event->start_date->format('d') }}</span>
                                            <small>{{ $event->start_date->format('m.Y') }}</small>
                                        </div>

                                        @if ($event->image)
                                            <div class="event-image-wrap">
                                                <img
                                                    src="{{ $event->image_url }}"
                                                    alt=""
                                                    class="event-image-preview"
                                                >
                                            </div>
                                        @endif
                                    </div>

                                    <div class="event-content">
                                        <h3 class="event-title mb-2">{{ $event->title }}</h3>
                                    </div>
                                </article>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-calendar-event"></i></div>
                                    <h3 class="empty-state-title">Подій поки немає</h3>
                                    <p class="empty-text mb-0">Коли адміністратор додасть майбутню подію, вона зʼявиться тут.</p>
                                </div>
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

                        @php
                            $testingLanguageLabels = [
                                'en' => 'Англійська',
                                'fr' => 'Французька',
                                'zh' => 'Китайська',
                            ];
                        @endphp

                        @if($availableTestingLanguages->isNotEmpty())
                            <div class="row g-3 mt-1">
                                @foreach($testingLanguageLabels as $code => $label)
                                    @if($availableTestingLanguages->contains($code))
                                        <div class="col-sm-6 col-md-4">
                                            <form action="{{ route('testing.start', $code) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-brand w-100">{{ $label }}</button>
                                            </form>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-light border mt-3 mb-0">
                                Нове тестування вже готується. Спробуйте трохи пізніше.
                            </div>
                        @endif

                        <div class="info-links mt-4">
                            <a href="#"
                               class="info-link"
                               data-bs-toggle="modal"
                               data-bs-target="#trialLessonRequestModal">
                                Записатись на безкоштовне групове заняття
                            </a>

                            <a href="{{ route('courses.index') }}" class="info-link">
                                Переглянути доступні онлайн-уроки
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
                                    @if($post->image)
                                        <span class="post-thumb">
                                            <img src="{{ $post->image_url }}" alt="">
                                        </span>
                                    @else
                                        <span class="post-number">{{ $loop->iteration }}</span>
                                    @endif

                                    <span class="post-body">
                                        <span class="post-title">{{ $post->title }}</span>
                                        <span class="post-excerpt">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($post->content), 90) }}
                                        </span>
                                        <span class="post-date">{{ $post->created_at->format('d.m.Y') }}</span>
                                    </span>
                                </a>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-journal-text"></i></div>
                                    <h3 class="empty-state-title">Публікацій поки немає</h3>
                                    <p class="empty-text mb-0">Коли зʼявляться новини школи або корисні матеріали, вони будуть тут.</p>
                                </div>
                            @endforelse
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
            if ($('.photo-gallery').length) {
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
            }
        });
    </script>
@endsection
