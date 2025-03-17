<!doctype html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Школа іноземних мов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        :root {
            --soft-green: #6cb2a3;
            --black: #000;
            --light-gray: #f8f9fa;
            --soft-red: #ff6b6b;
            --hover-gray: #495057;
            --button-color: #5ac3a4; /* Колір кнопки */
            --main-bg: #f2f2f2;
            --card-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
        }

        body {
            background-color: var(--main-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--black);
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: white !important;
            border-bottom: 4px solid var(--black);
            box-shadow: var(--card-shadow);
            padding: 10px 0;
        }

        .navbar-brand img {
            max-height: 60px; /* Логотип в два рази вищий */
            width: auto;
        }

        .navbar-toggler {
            border-color: var(--black);
        }

        .navbar-nav {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .nav-item {
            margin: 0 15px;
            position: relative; /* Для розташування випадаючих меню */
        }

        .nav-link {
            font-size: 1rem;
            font-weight: 600;
            color: var(--black);
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .nav-link:hover {
            color: var(--soft-green);
            transform: scale(1.05);
        }

        /* Додано ефект відкриття меню на наведенні */
        .nav-item.dropdown:hover .dropdown-menu {
            display: block;
            opacity: 1;
            visibility: visible;
            transition: visibility 0.3s, opacity 0.3s;
        }

        .dropdown-menu {
            border-radius: 10px;
            border: 2px solid var(--black);
            position: absolute;
            top: 100%;
            left: 0;
            display: none;
        }

        .dropdown-item {
            font-weight: 500;
            color: var(--black);
        }

        .dropdown-item:hover {
            color: var(--soft-green);
            background-color: var(--light-gray);
        }

        .social-icons a {
            margin-left: 15px;
            font-size: 1.8rem;
            color: var(--button-color);
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .social-icons a:hover {
            transform: scale(1.1);
            color: var(--soft-red);
        }

        .btn-register {
            background-color: var(--button-color);
            border: 2px solid var(--black);
            color: var(--black);
            font-weight: bold;
            transition: 0.3s;
            font-size: 1.1rem;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .btn-register:hover {
            background-color: var(--black);
            color: white;
            transform: scale(1.05);
        }

        @media (max-width: 767px) {
            .navbar-nav {
                flex-direction: column;
                align-items: center;
            }

            .social-icons a {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a href="#" class="btn btn-register">📚 Запис на безкоштовне заняття</a>
        <a class="navbar-brand mx-auto" href="{{route('index')}}">
            <img src="{{ asset('images/logo.png') }}" alt="Школа іноземних мов">
        </a>
        <div class="social-icons d-flex">
            <a href="#"><i class="bi bi-instagram"></i></a>
            <a href="#"><i class="bi bi-telegram"></i></a>
            <a href="#"><i class="bi bi-telephone"></i></a>
            <a href="#"><i class="bi bi-facebook"></i></a>
            <a href="#"><i class="bi bi-tiktok"></i></a> <!-- Додано TikTok -->
        </div>
    </div>
</nav>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{{route('index')}}">Головна сторінка</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">Онлайн навчання</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Доступні курси</a></li>
                        <li><a class="dropdown-item" href="#">Словник</a></li>
                        <li><a class="dropdown-item" href="#">Відео</a></li>
                        <li><a class="dropdown-item" href="#">Оплата</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">Про нас</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Наші вчителі</a></li>
                        <li><a class="dropdown-item" href="#">Адрес</a></li>
                        <li><a class="dropdown-item" href="#">Правила Школи</a></li>
                    </ul>
                </li>
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Увійти</a>
                    </li>
                @endguest
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#">Профіль</a></li>
                            @if(Auth::user()->role === 'admin') <!-- Перевірка на роль admin -->
                            <li><a class="dropdown-item" href="{{ route('admin.index') }}">Кабінет адміністратора</a></li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Вийти
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
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

<div class="container mt-4 fade-in">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</body>
</html>
Справа від імʼя користувача, якщо заходить user зі "role" admin, то щоб зʼявлялось посилання "Кабінет адміністратора"
