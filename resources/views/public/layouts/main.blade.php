<!doctype html>
<html lang="uk">
<head>
    @php
        $seoTitle = trim($__env->yieldContent('title')) ?: config('seo.default_title');
        $seoDescription = trim($__env->yieldContent('description')) ?: config('seo.default_description');
        $seoRobots = trim($__env->yieldContent('robots')) ?: \App\Support\Seo::robotsFor(request());
        $seoCanonical = trim($__env->yieldContent('canonical')) ?: url()->current();
        $seoImage = trim($__env->yieldContent('image')) ?: asset(config('seo.default_image'));
        $seoType = trim($__env->yieldContent('og_type')) ?: 'website';
        $googleAnalyticsId = config('services.google_analytics.measurement_id');
        $googleSiteVerification = config('services.google_search_console.verification');
        $analyticsEvent = session('analytics_event');
        $analyticsPageView = $seoRobots === \App\Support\Seo::PUBLIC_ROBOTS;
        $analyticsEnabled = filled($googleAnalyticsId) && ($analyticsPageView || is_array($analyticsEvent));
        $business = config('seo.business');
        $localBusinessSchema = [
            '@context' => 'https://schema.org',
            '@type' => $business['legal_type'],
            '@id' => route('index') . '#organization',
            'name' => $business['name'],
            'description' => $business['description'],
            'url' => route('index'),
            'logo' => asset($business['logo']),
            'image' => asset($business['image']),
            'telephone' => $business['telephone'],
            'priceRange' => $business['price_range'],
            'hasMap' => $business['map_url'],
            'address' => array_merge(['@type' => 'PostalAddress'], $business['address']),
            'geo' => array_merge(['@type' => 'GeoCoordinates'], $business['geo']),
            'openingHoursSpecification' => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => collect($business['opening_hours']['days'])
                    ->map(fn ($day) => 'https://schema.org/' . $day)
                    ->all(),
                'opens' => $business['opening_hours']['opens'],
                'closes' => $business['opening_hours']['closes'],
            ],
            'areaServed' => $business['area_served'],
            'sameAs' => $business['same_as'],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Заняття з англійської мови',
                'itemListElement' => collect($business['offers'])
                    ->map(fn ($offer) => [
                        '@type' => 'Offer',
                        'price' => $offer['price'],
                        'priceCurrency' => 'UAH',
                        'itemOffered' => [
                            '@type' => 'Service',
                            'name' => $offer['name'],
                            'description' => $offer['description'],
                        ],
                    ])
                    ->values()
                    ->all(),
            ],
        ];
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => route('index') . '#website',
            'url' => route('index'),
            'name' => $business['name'],
            'inLanguage' => 'uk-UA',
            'publisher' => ['@id' => route('index') . '#organization'],
        ];
    @endphp

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:locale" content="uk_UA">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:site_name" content="{{ $business['name'] }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(filled($googleSiteVerification))
        <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif

    @if($analyticsEnabled)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($googleAnalyticsId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}

            gtag('consent', 'default', {
                analytics_storage: 'denied',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied'
            });

            if (window.localStorage.getItem('school_cookie_consent') === 'accepted') {
                gtag('consent', 'update', { analytics_storage: 'granted' });
            }

            gtag('js', new Date());
            gtag('config', @json($googleAnalyticsId), {
                send_page_view: @json($analyticsPageView)
            });
        </script>
    @endif

    @if(request()->routeIs('index', 'contact.index'))
        <script type="application/ld+json">{!! json_encode($localBusinessSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    @if(request()->routeIs('index'))
        <script type="application/ld+json">{!! json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    @stack('structured-data')

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
               data-bs-target="#trialLessonRequestModal"
               data-analytics-event="view_trial_lesson_form"
               data-analytics-label="header">
                <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i>
                <span class="btn-register-label-desktop">Запис на безкоштовне заняття</span>
                <span class="btn-register-label-mobile">Пробний урок</span>
            </a>

            <button class="navbar-toggler mobile-menu-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav"
                    aria-controls="navbarNav"
                    aria-expanded="false"
                    aria-label="Перемкнути навігацію">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <a class="site-logo" href="{{ route('index') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Школа іноземних мов">
            </a>

            <div class="social-icons">
                <a href="https://www.instagram.com/korporatsiia.mov/" target="_blank" rel="noopener" aria-label="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="https://t.me/DashaYurchak" target="_blank" rel="noopener" aria-label="Telegram"
                   data-analytics-event="contact" data-analytics-label="telegram_header">
                    <i class="bi bi-telegram"></i>
                </a>
                <a href="tel:+380662992218" aria-label="Телефон"
                   data-analytics-event="contact" data-analytics-label="phone_header">
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
            <button class="navbar-toggler navbar-main-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
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
                            Навчання
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('seo.show', ['slug' => 'shkola-angliiskoi-brovary']) }}">Англійська у Броварах</a></li>
                            <li><a class="dropdown-item" href="{{ route('seo.show', ['slug' => 'angliiska-dlia-ditei']) }}">Для дітей</a></li>
                            <li><a class="dropdown-item" href="{{ route('seo.show', ['slug' => 'angliiska-dlia-shkoliariv']) }}">Для школярів</a></li>
                            <li><a class="dropdown-item" href="{{ route('seo.show', ['slug' => 'angliiska-dlia-doroslykh']) }}">Для дорослих</a></li>
                            <li><a class="dropdown-item" href="{{ route('seo.show', ['slug' => 'pidgotovka-do-nmt-evi']) }}">Підготовка до НМТ та ЄВІ</a></li>
                            <li><a class="dropdown-item" href="{{ route('seo.show', ['slug' => 'pidgotovka-do-ielts']) }}">Підготовка до IELTS</a></li>
                        </ul>
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
                            <li><a class="dropdown-item" href="{{ route('rules.index') }}">Правила школи</a></li>
                            <li><a class="dropdown-item" href="{{ route('privacy-policy') }}">Політика конфіденційності</a></li>
                            <li><a class="dropdown-item" href="{{ route('data-deletion') }}">Видалення даних</a></li>
                        </ul>
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
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item dropdown-item-button">
                                            Вийти
                                        </button>
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

<footer class="site-footer">
    <div class="container">
        <div class="site-footer-grid">
            <div class="site-footer-brand">
                <a class="site-footer-logo" href="{{ route('index') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="Корпорація Мов">
                </a>
                <p>Школа іноземних мов у Броварах. Навчання онлайн та офлайн для дітей і дорослих.</p>
            </div>

            <nav class="site-footer-nav" aria-label="Навігація в нижній частині сторінки">
                <h2>Навігація</h2>
                <a href="{{ route('seo.show', ['slug' => 'shkola-angliiskoi-brovary']) }}">Англійська у Броварах</a>
                <a href="{{ route('seo.show', ['slug' => 'angliiska-online']) }}">Англійська онлайн</a>
                <a href="{{ route('seo.show', ['slug' => 'angliiska-dlia-shkoliariv']) }}">Англійська для школярів</a>
                <a href="{{ route('courses.index') }}">Курси та уроки</a>
                <a href="{{ route('teachers.index') }}">Наші вчителі</a>
                <a href="{{ route('rules.index') }}">Правила школи</a>
                <a href="{{ route('contact.index') }}">Контакти</a>
            </nav>

            <div class="site-footer-contact">
                <h2>Зв’язатися з нами</h2>
                <a href="tel:+380662992218"
                   data-analytics-event="contact" data-analytics-label="phone_footer">
                    <i class="bi bi-telephone" aria-hidden="true"></i>
                    +38 (066) 299-22-18
                </a>
                <a href="https://t.me/DashaYurchak" target="_blank" rel="noopener"
                   data-analytics-event="contact" data-analytics-label="telegram_footer">
                    <i class="bi bi-telegram" aria-hidden="true"></i>
                    Telegram
                </a>
                <a href="https://www.instagram.com/korporatsiia.mov/" target="_blank" rel="noopener">
                    <i class="bi bi-instagram" aria-hidden="true"></i>
                    Instagram
                </a>
            </div>
        </div>

        <div class="site-footer-bottom">
            <span>© {{ now()->year }} Корпорація Мов</span>
            <div class="site-footer-legal">
                <a href="{{ route('privacy-policy') }}">Політика конфіденційності</a>
                <a href="{{ route('data-deletion') }}">Видалення даних</a>
            </div>
        </div>
    </div>
</footer>

@if($analyticsEnabled)
    <aside class="cookie-consent" data-cookie-consent hidden aria-label="Налаштування аналітичних cookie">
        <p>
            Ми використовуємо аналітичні cookie, щоб розуміти, як покращити сайт.
            <a href="{{ route('privacy-policy') }}">Детальніше</a>
        </p>
        <div class="cookie-consent-actions">
            <button type="button" class="cookie-consent-decline" data-cookie-decline>Лише необхідні</button>
            <button type="button" class="cookie-consent-accept" data-cookie-accept>Дозволити аналітику</button>
        </div>
    </aside>
@endif

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

@if($analyticsEnabled)
    <script>
        (() => {
            const consentKey = 'school_cookie_consent';
            const banner = document.querySelector('[data-cookie-consent]');
            const savedConsent = window.localStorage.getItem(consentKey);

            if (banner && !savedConsent) {
                banner.hidden = false;
            }

            document.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
                window.localStorage.setItem(consentKey, 'accepted');
                gtag('consent', 'update', { analytics_storage: 'granted' });
                banner.hidden = true;
            });

            document.querySelector('[data-cookie-decline]')?.addEventListener('click', () => {
                window.localStorage.setItem(consentKey, 'declined');
                banner.hidden = true;
            });

            document.addEventListener('click', (event) => {
                const target = event.target.closest('[data-analytics-event]');

                if (!target) {
                    return;
                }

                gtag('event', target.dataset.analyticsEvent, {
                    event_label: target.dataset.analyticsLabel || undefined
                });
            });

            const serverEvent = @json($analyticsEvent);

            if (serverEvent?.name) {
                gtag('event', serverEvent.name, serverEvent.parameters || {});
            }
        })();
    </script>
@endif
</body>
</html>
