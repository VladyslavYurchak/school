<?php

return [
    'live_enabled' => (bool) env('SOCIAL_PUBLISHING_LIVE_ENABLED', false),

    'providers' => [
        'facebook' => (bool) env('SOCIAL_PUBLISHING_FACEBOOK_ENABLED', false),
        'instagram' => (bool) env('SOCIAL_PUBLISHING_INSTAGRAM_ENABLED', false),
        'tiktok' => (bool) env('SOCIAL_PUBLISHING_TIKTOK_ENABLED', false),
    ],
];
