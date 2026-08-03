<?php

return [
    'default_title' => 'Школа англійської мови у Броварах | Корпорація Мов',
    'default_description' => 'Англійська для дітей від 7 років і дорослих у ЖК Scandia, Бровари. Онлайн та офлайн, індивідуальні, парні й групові заняття. Безкоштовне пробне заняття 30 хвилин.',
    'default_image' => 'images/meta-app-icon.png',

    'business' => [
        'name' => 'Корпорація Мов',
        'legal_type' => ['School', 'LocalBusiness'],
        'description' => 'Школа англійської мови для дітей від 7 років і дорослих у ЖК Scandia, Бровари. Онлайн та офлайн заняття, підготовка до НМТ, ЄВІ та IELTS.',
        'telephone' => '+380662992218',
        'price_range' => '₴₴',
        'logo' => 'images/logo.png',
        'image' => 'images/meta-app-icon.png',
        'map_url' => 'https://maps.app.goo.gl/VE7SfEG7ELQosbbX9',
        'address' => [
            'streetAddress' => 'вул. Героїв Крут, 12, ЖК Scandia, 1 поверх',
            'addressLocality' => 'Бровари',
            'addressRegion' => 'Київська область',
            'postalCode' => '07400',
            'addressCountry' => 'UA',
        ],
        'geo' => [
            'latitude' => 50.48758937159926,
            'longitude' => 30.7585762767716,
        ],
        'opening_hours' => [
            'days' => [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
            ],
            'opens' => '09:00',
            'closes' => '19:00',
        ],
        'area_served' => [
            'Бровари',
            'ЖК Scandia',
            'Київ',
            'Україна онлайн',
        ],
        'same_as' => [
            'https://www.instagram.com/korporatsiia.mov/',
            'https://www.facebook.com/people/%D0%9A%D0%BE%D1%80%D0%BF%D0%BE%D1%80%D0%B0%D1%86%D1%96%D1%8F-%D0%BC%D0%BE%D0%B2/61558067528774/',
            'https://www.tiktok.com/@korporatsiia.mov',
        ],
        'offers' => [
            [
                'name' => 'Індивідуальні заняття двічі на тиждень',
                'description' => '8–9 занять на місяць тривалістю 55 хвилин',
                'price' => 4900,
            ],
            [
                'name' => 'Індивідуальні заняття тричі на тиждень',
                'description' => '12–13 занять на місяць тривалістю 55 хвилин',
                'price' => 6800,
            ],
            [
                'name' => 'Парні заняття двічі на тиждень',
                'description' => '8–9 занять на місяць тривалістю 55 хвилин',
                'price' => 3450,
            ],
            [
                'name' => 'Парні заняття тричі на тиждень',
                'description' => '12–13 занять на місяць тривалістю 55 хвилин',
                'price' => 4800,
            ],
            [
                'name' => 'Групові заняття двічі на тиждень',
                'description' => 'До 4 учнів у групі, 8–9 занять на місяць тривалістю 55 хвилин',
                'price' => 3200,
            ],
            [
                'name' => 'Безкоштовне пробне заняття',
                'description' => 'Знайомство, визначення потреб і формату навчання, 30 хвилин',
                'price' => 0,
            ],
        ],
    ],

    'noindex_paths' => [
        'admin',
        'admin/*',
        'student',
        'student/*',
        'login',
        'register',
        'logout',
        'password/*',
        'email/*',
        'auth/*',
        'home',
        'payments',
        'testing/session/*',
        'monopay/*',
        'telegram/*',
    ],
];
