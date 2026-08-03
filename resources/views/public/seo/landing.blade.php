@extends('public.layouts.main')

@section('title', $page['title'])
@section('description', $page['description'])
@section('canonical', route('seo.show', ['slug' => $slug]))

@php
    $business = config('seo.business');
    $offers = $business['offers'];
    $serviceUrl = route('seo.show', ['slug' => $slug]);
    $serviceSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        '@id' => $serviceUrl . '#service',
        'name' => $page['heading'],
        'description' => $page['description'],
        'url' => $serviceUrl,
        'provider' => ['@id' => route('index') . '#organization'],
        'areaServed' => $business['area_served'],
        'availableChannel' => [
            ['@type' => 'ServiceChannel', 'serviceLocation' => array_merge(['@type' => 'Place'], ['address' => array_merge(['@type' => 'PostalAddress'], $business['address'])])],
            ['@type' => 'ServiceChannel', 'serviceUrl' => $serviceUrl, 'availableLanguage' => ['uk', 'en']],
        ],
        'offers' => collect($offers)->map(fn ($offer) => [
            '@type' => 'Offer',
            'price' => $offer['price'],
            'priceCurrency' => 'UAH',
            'name' => $offer['name'],
            'description' => $offer['description'],
        ])->values()->all(),
    ];
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($page['faq'])->map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
        ])->values()->all(),
    ];
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Головна', 'item' => route('index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $page['heading'], 'item' => $serviceUrl],
        ],
    ];
@endphp

@push('structured-data')
    <script type="application/ld+json">{!! json_encode($serviceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    <article class="service-landing">
        <section class="service-hero">
            <div class="container service-hero-inner">
                <nav class="service-breadcrumbs" aria-label="Хлібні крихти">
                    <a href="{{ route('index') }}">Головна</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span>{{ $page['heading'] }}</span>
                </nav>

                <div class="service-hero-content">
                    <span class="service-eyebrow">{{ $page['eyebrow'] }}</span>
                    <h1>{{ $page['heading'] }}</h1>
                    <p>{{ $page['lead'] }}</p>

                    <div class="service-actions">
                        <button type="button"
                                class="btn-brand"
                                data-bs-toggle="modal"
                                data-bs-target="#trialLessonRequestModal"
                                data-analytics-event="view_trial_lesson_form"
                                data-analytics-label="seo_{{ $slug }}">
                            <i class="bi bi-calendar-check" aria-hidden="true"></i>
                            Записатися на пробне заняття
                        </button>
                        <a class="btn-brand-outline"
                           href="tel:+380662992218"
                           data-analytics-event="contact"
                           data-analytics-label="phone_seo_{{ $slug }}">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                            +38 (066) 299-22-18
                        </a>
                    </div>
                </div>

                <ul class="service-quick-facts" aria-label="Коротко про навчання">
                    <li><strong>30 хв</strong><span>пробне заняття безкоштовно</span></li>
                    <li><strong>55 хв</strong><span>тривалість звичайного уроку</span></li>
                    <li><strong>до 4</strong><span>учнів у групі</span></li>
                    <li><strong>7+</strong><span>навчаємо дітей і дорослих</span></li>
                </ul>
            </div>
        </section>

        <section class="service-band service-band-white">
            <div class="container">
                <div class="service-highlight-grid">
                    @foreach($page['highlights'] as $highlight)
                        <div class="service-highlight">
                            <i class="bi {{ $highlight['icon'] }}" aria-hidden="true"></i>
                            <h2>{{ $highlight['title'] }}</h2>
                            <p>{{ $highlight['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="service-band">
            <div class="container service-two-column">
                <div>
                    <span class="service-section-label">Кому підходить</span>
                    <h2>{{ $page['audience_title'] }}</h2>
                </div>
                <ul class="service-check-list">
                    @foreach($page['audience'] as $item)
                        <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="service-band service-band-white">
            <div class="container">
                <div class="service-section-heading">
                    <span class="service-section-label">Навчальний процес</span>
                    <h2>{{ $page['program_title'] }}</h2>
                </div>
                <ol class="service-program-list">
                    @foreach($page['program'] as $step)
                        <li>
                            <span class="service-program-number">{{ $loop->iteration }}</span>
                            <div>
                                <h3>{{ $step['title'] }}</h3>
                                <p>{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="service-band">
            <div class="container">
                <div class="service-section-heading">
                    <span class="service-section-label">Результат</span>
                    <h2>{{ $page['outcomes_title'] }}</h2>
                </div>
                <div class="service-outcomes">
                    @foreach($page['outcomes'] as $outcome)
                        <div><i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i><span>{{ $outcome }}</span></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="service-band service-band-white" id="prices">
            <div class="container">
                <div class="service-section-heading">
                    <span class="service-section-label">Формати й ціни</span>
                    <h2>Оберіть комфортний формат навчання</h2>
                    <p>Ціни для парних і групових занять указано за одного учня на місяць.</p>
                </div>

                <div class="service-price-grid">
                    <div class="service-price-card">
                        <i class="bi bi-person" aria-hidden="true"></i>
                        <h3>Індивідуально</h3>
                        <p><strong>{{ number_format($offers[0]['price'], 0, ',', ' ') }} грн</strong> — 2 рази на тиждень</p>
                        <p><strong>{{ number_format($offers[1]['price'], 0, ',', ' ') }} грн</strong> — 3 рази на тиждень</p>
                    </div>
                    <div class="service-price-card">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <h3>У парі</h3>
                        <p><strong>{{ number_format($offers[2]['price'], 0, ',', ' ') }} грн</strong> — 2 рази на тиждень</p>
                        <p><strong>{{ number_format($offers[3]['price'], 0, ',', ' ') }} грн</strong> — 3 рази на тиждень</p>
                    </div>
                    <div class="service-price-card">
                        <i class="bi bi-people-fill" aria-hidden="true"></i>
                        <h3>У групі</h3>
                        <p><strong>{{ number_format($offers[4]['price'], 0, ',', ' ') }} грн</strong> — 2 рази на тиждень</p>
                        <p>До 4 учнів зі схожим рівнем і метою.</p>
                    </div>
                </div>

                <p class="service-price-note">Кількість уроків залежить від календарного місяця: 8–9 при графіку двічі на тиждень або 12–13 при графіку тричі.</p>
            </div>
        </section>

        <section class="service-band">
            <div class="container service-location-band">
                <div>
                    <span class="service-section-label">Офлайн і онлайн</span>
                    <h2>Навчайтеся у Броварах або з будь-якого міста</h2>
                    <p>Офлайн: ЖК Scandia, вул. Героїв Крут, 12, перший поверх. Понеділок–субота, 09:00–19:00.</p>
                </div>
                <div class="service-location-actions">
                    <a href="{{ $business['map_url'] }}" class="btn-brand-outline" target="_blank" rel="noopener">
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        Відкрити на Google Maps
                    </a>
                    <a href="{{ route('contact.index') }}" class="service-text-link">Усі контакти <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <section class="service-band service-band-white">
            <div class="container service-faq-layout">
                <div>
                    <span class="service-section-label">FAQ</span>
                    <h2>Поширені запитання</h2>
                </div>
                <div class="service-faq-list">
                    @foreach($page['faq'] as $item)
                        <details>
                            <summary>{{ $item['question'] }}<i class="bi bi-plus-lg" aria-hidden="true"></i></summary>
                            <p>{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        @if($relatedPages->isNotEmpty())
            <section class="service-band">
                <div class="container">
                    <div class="service-section-heading">
                        <span class="service-section-label">Корисні сторінки</span>
                        <h2>Інші варіанти навчання</h2>
                    </div>
                    <div class="service-related-grid">
                        @foreach($relatedPages as $relatedPage)
                            <a href="{{ route('seo.show', ['slug' => $relatedPage['slug']]) }}">
                                <h3>{{ $relatedPage['title'] }}</h3>
                                <p>{{ $relatedPage['summary'] }}</p>
                                <span>Дізнатися більше <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="service-cta">
            <div class="container service-cta-inner">
                <div>
                    <span>Перший крок безкоштовний</span>
                    <h2>Познайомимося та допоможемо обрати формат</h2>
                    <p>Пробне заняття триває 30 хвилин. Залиште контакти — адміністратор зв’яжеться з вами.</p>
                </div>
                <button type="button"
                        class="btn-brand"
                        data-bs-toggle="modal"
                        data-bs-target="#trialLessonRequestModal"
                        data-analytics-event="view_trial_lesson_form"
                        data-analytics-label="seo_cta_{{ $slug }}">
                    Записатися безкоштовно
                </button>
            </div>
        </section>
    </article>
@endsection
