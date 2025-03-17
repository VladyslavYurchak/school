@extends('index.layouts.main')

@section('content')
    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="p-4 shadow-lg border-0 rounded-3 bg-white h-100 d-flex align-items-center justify-content-center">
                    <!-- Слайдер фото -->
                    <div class="photo-gallery-wrapper">
                        <h2 class="photo-gallery-title">НАШІ ФОТО</h2>
                        <div class="photo-gallery">
                            @foreach ($photos as $photo)
                                <div>
                                    <img src="{{ asset('storage/' . $photo->path) }}" alt="Фото" class="img-fluid">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-4 shadow-lg border-0 rounded-3 bg-white h-100">
                    <h2 class="section-title">Заплановані події</h2>
                    <ul class="list-unstyled">
                        @foreach ($events as $event)
                            <li class="event-item">
                                <div class="event-date">{{ \Carbon\Carbon::parse($event->start_date)->toDateString() }}</div>
                                <div class="event-title">{{ $event->title }}</div>
                                @if ($event->image)
                                    <div class="event-image" style="background-image: url('{{ asset('storage/' . $event->image) }}');"></div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-4 shadow-lg border-0 rounded-3 bg-white h-100 text-center">
                    <h2 class="section-title">Перевір свої знання іноземної мови безкоштовно</h2>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <button class="btn btn-tiffany">Англійська</button>
                        <button class="btn btn-tiffany">Німецька</button>
                        <button class="btn btn-tiffany">Французька</button>
                    </div>
                    <hr>
                    <h5 class="fw-bold"><a href="#" class="text-decoration-none text-black">Записатись на безкоштовне групове заняття</a></h5>
                    <hr>
                    <h5 class="fw-bold"><a href="#" class="text-decoration-none text-black">Отримати безкоштовний доступ до уроків, відеоматеріалів та спільноти Корпорації МОВ</a></h5>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-4 shadow-lg border-0 rounded-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold">Останні пости</h5>
                    </div>
                    <ul class="list-unstyled mt-3">
                        @foreach ($posts as $post)
                            <li>{{ $loop->iteration }}. <a href="{{ route('posts.show', $post->id) }}" class="post-link">{{ $post->title }}</a></li>
                        @endforeach
                    </ul>
                    <div class="mt-3 d-flex justify-content-center">
                        <nav>
                            <ul class="pagination pagination-sm">
                                @if ($posts->lastPage() > 1)
                                    <li class="page-item {{ ($posts->currentPage() == 1) ? ' active' : '' }}">
                                        <a class="page-link" href="{{ $posts->url(1) }}">1</a>
                                    </li>
                                    @if ($posts->currentPage() > 3)
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    @endif
                                    @if ($posts->currentPage() > 2)
                                        <li class="page-item"><a class="page-link" href="{{ $posts->url($posts->currentPage() - 1) }}">{{ $posts->currentPage() - 1 }}</a></li>
                                    @endif
                                    @if ($posts->currentPage() > 1 && $posts->currentPage() < $posts->lastPage())
                                        <li class="page-item active"><span class="page-link">{{ $posts->currentPage() }}</span></li>
                                    @endif
                                    @if ($posts->currentPage() < $posts->lastPage() - 1)
                                        <li class="page-item"><a class="page-link" href="{{ $posts->url($posts->currentPage() + 1) }}">{{ $posts->currentPage() + 1 }}</a></li>
                                    @endif
                                    @if ($posts->currentPage() < $posts->lastPage() - 2)
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    @endif
                                    <li class="page-item {{ ($posts->currentPage() == $posts->lastPage()) ? ' active' : '' }}">
                                        <a class="page-link" href="{{ $posts->url($posts->lastPage()) }}">{{ $posts->lastPage() }}</a>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-tiffany {
            background-color: #4ca8a1;
            border: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .btn-tiffany:hover {
            background-color: #3a8f8a;
            transform: scale(1.05);
        }

        .post-link {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .post-link:hover {
            color: #4ca8a1;
        }

        .pagination-sm .page-link {
            font-size: 0.875rem;
            padding: 5px 10px;
        }

        /* Стилі для слайдера */
        .photo-gallery-wrapper {
            text-align: center; /* Вирівнює заголовок і галерею */
        }

        .photo-gallery-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Контейнер для фото */
        .photo-gallery {
            width: 350px;
            height: 350px;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 12px;
            border: 4px solid #e0e0e0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Фото всередині */
        .photo-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        /* Стилі для подій */
        .event-item {
            position: relative;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 10px;
            transition: background 0.3s ease;
        }

        .event-item:hover {
            background-color: #f7f7f7;
        }

        .event-date {
            font-size: 14px;
            color: #888;
        }

        .event-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }

        .event-item:hover .event-image {
            opacity: 1;
        }

        .event-image {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        /* Стиль для заголовків */
        .section-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            letter-spacing: 1px;
        }
    </style>

    <!-- Підключення Slick Slider CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css"/>
@endsection

