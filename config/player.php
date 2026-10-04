<?php

return [
    // Latest stable Web SDK, verified against Aliyun's release notes on 2026-10-04.
    'sdk_version' => '2.39.0',
    'license_domain' => env('ALIYUN_PLAYER_LICENSE_DOMAIN', ''),
    'license_key' => env('ALIYUN_PLAYER_LICENSE_KEY', ''),

    // Demo content is opt-in and only rendered in the local environment.
    'demo_enabled' => env('ALIYUN_PLAYER_DEMO', false),
    'demo_source' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
    'demo_poster' => '/images/website.svg',
];
