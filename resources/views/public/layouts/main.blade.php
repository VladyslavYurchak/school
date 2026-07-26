<!doctype html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Школа іноземних мов</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/favicon-180x180.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body
    class="site-body"
    style="--site-pattern: url('{{ asset('images/pattern1.png') }}');"
>

<header class="site-header">
    <div class="topbar">
        <div class="container topbar-inner">
            <a href="#"
               class="btn-register"
               data-bs-toggle="modal"
               data-bs-target="#trialLessonRequestModal">
                📚 Запис на безкоштовне заняття
            </a>

            <a class="site-logo" href="{{ route('index') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Школа іноземних мов">
            </a>

            <div class="social-icons">
                <a href="https://www.instagram.com/korporatsiia.mov/" target="_blank" rel="noopener" aria-label="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="https://t.me/DashaYurchak" target="_blank" rel="noopener" aria-label="Telegram">
                    <i class="bi bi-telegram"></i>
                </a>
                <a href="tel:+380662992218" aria-label="Телефон">
                    <i class="bi bi-telephone"></i>
                </a>
                <a href="https://www.facebook.com/people/%D0%9A%D0%BE%D1%80%D0%BF%D0%BE%D1%80%D0%B0%D1%86%D1%96%D1%8F-%D0%BC%D0%BE%D0%B2/61558067528774/" target="_blank" rel="noopener" aria-label="Facebook">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="https://www.tiktok.com/@korporatsiia.mov" target="_blank" rel="noopener" aria-label="TikTok">
                    <i class="bi bi-tiktok"></i>
                </a>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg site-navbar">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Перемкнути навігацію">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('index') }}">Головна сторінка</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            Онлайн навчання
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('courses.index') }}">Доступні курси</a></li>
                            @auth
                                @if(Auth::user()->isStudent())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('student.vocabulary.learn') }}">
                                            Мій словник
                                        </a>
                                    </li>
                                @endif
                            @endauth
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            Про нас
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('teachers.index') }}">Наші вчителі</a></li>
                            <li><a class="dropdown-item" href="{{ route('contact.index') }}">Адреса</a></li>
                            <li><a class="dropdown-item" href="{{ route('rules.index') }}">Правила школи</a></li>                        </ul>
                    </li>

                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Увійти</a>
                        </li>
                    @endguest

                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                @if(Auth::user()->role === 'admin')
                                    <li><a class="dropdown-item" href="{{ route('admin.index') }}">Кабінет адміністратора</a></li>
                                @endif

                                @if(Auth::user()->role === 'student')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('student.dashboard') }}">
                                            Кабінет учня
                                        </a>
                                    </li>
                                @endif

                                @if(Auth::user()->role === 'teacher')
                                    <li><a class="dropdown-item" href="{{ route('admin.index') }}">Кабінет вчителя</a></li>
                                @endif

                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Вийти
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="site-main">
    @if(session('error'))
        <div class="container pt-3">
            <div class="alert alert-danger mb-0" role="alert">
                {{ session('error') }}
            </div>
        </div>
    @endif

    @if(session('trial_request_success'))
        <div class="container pt-3">
            <div class="alert alert-success mb-0">
                {{ session('trial_request_success') }}
            </div>
        </div>
    @endif

    @yield('content')
</main>

<div class="modal fade" id="trialLessonRequestModal" tabindex="-1" aria-labelledby="trialLessonRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('trial-lesson-requests.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="trialLessonRequestModalLabel">Запис на безкоштовне заняття</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-3">
                    <label for="trial-name" class="form-label">Ім’я</label>
                    <input type="text" class="form-control" id="trial-name" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="trial-phone" class="form-label">Телефон</label>
                    <input type="text" class="form-control" id="trial-phone" name="phone" value="{{ old('phone') }}" required>
                </div>

                <div class="mb-3">
                    <label for="trial-email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="trial-email" name="email" value="{{ old('email') }}">
                </div>

                <div class="mb-3">
                    <label for="trial-contact" class="form-label">Як краще зв’язатися?</label>
                    <select class="form-select" id="trial-contact" name="preferred_contact">
                        <option value="">Не важливо</option>
                        <option value="phone" @selected(old('preferred_contact') === 'phone')>Телефон</option>
                        <option value="telegram" @selected(old('preferred_contact') === 'telegram')>Telegram</option>
                        <option value="email" @selected(old('preferred_contact') === 'email')>Email</option>
                    </select>
                </div>

                <div class="mb-0">
                    <label for="trial-notes" class="form-label">Коментар</label>
                    <textarea class="form-control" id="trial-notes" name="notes" rows="3" placeholder="Вік, мова, рівень або зручний час">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрити</button>
                <button type="submit" class="btn btn-primary">Надіслати заявку</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
