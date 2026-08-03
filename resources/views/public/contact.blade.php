@extends('public.layouts.main')

@section('title', 'Контакти школи англійської у Броварах | Корпорація Мов')
@section('description', 'Адреса школи Корпорація Мов: ЖК Scandia, вул. Героїв Крут, 12, Бровари, 1 поверх. Телефон +38 (066) 299-22-18, онлайн та офлайн заняття.')

@section('content')
    <div class="contact-page py-5">
        <div class="container">

            <section class="contact-hero mb-5">
                <div class="contact-hero-card">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-7">
                            <span class="contact-badge">Корпорація Мов</span>
                            <h1 class="contact-page-title mb-3">Адреса та контакти</h1>
                            <p class="contact-page-text mb-0">
                                Завітайте до нас або зв’яжіться зручним для вас способом.
                                Ми завжди раді допомогти вам обрати формат навчання та відповісти на питання.
                            </p>
                        </div>

                        <div class="col-lg-5">
                            <div class="contact-hero-side">
                                <div class="contact-hero-side-title">Графік роботи</div>
                                <div class="contact-hero-side-text">
                                    Понеділок – Субота<br>
                                    09:00 – 19:00
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="contact-main">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="contact-info-card h-100">
                            <h2 class="contact-section-title">Наша адреса</h2>

                            <div class="contact-item">
                                <div class="contact-item-label">Адреса</div>
                                <div class="contact-item-value">
                                    м. Бровари, вул. Героїв Крут, 12.<br>
                                    ЖК Scandia, Brovary
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-item-label">Телефон</div>
                                <div class="contact-item-value">
                                    <a href="tel:+380662992218">+38 (066) 299-22-18</a>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-item-label">Соцмережі</div>
                                <div class="contact-socials">
                                    <a href="https://www.instagram.com/korporatsiia.mov/" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                    <a href="https://t.me/DashaYurchak" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                                    <a href="https://www.facebook.com/people/%D0%9A%D0%BE%D1%80%D0%BF%D0%BE%D1%80%D0%B0%D1%86%D1%96%D1%8F-%D0%BC%D0%BE%D0%B2/61558067528774/" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                    <a href="https://www.tiktok.com/@korporatsiia.mov" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="https://maps.app.goo.gl/VE7SfEG7ELQosbbX9"
                                   target="_blank"
                                   rel="noopener"
                                   class="btn btn-brand">
                                    Прокласти маршрут
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="contact-map-card h-100">
                            <h2 class="contact-section-title">Ми на карті</h2>

                            <div class="contact-map-wrap">
                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2538.503330211286!2d30.7585762767716!3d50.48758937159926!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40d4db0cf9da49c7%3A0x166e786a7a8a1259!2sKorporatsiya%20Mov%20-%20Shkola%20Inozemnykh%20Mov!5e0!3m2!1sen!2sus!4v1776331186461!5m2!1sen!2sus"
                                        width="600"
                                        height="450"
                                        style="border:0;"
                                        allowfullscreen=""
                                        loading="lazy"
                                        title="Корпорація Мов на Google Maps"
                                        referrerpolicy="no-referrer-when-downgrade"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
@endsection
