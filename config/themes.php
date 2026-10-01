<?php

return [
    'default' => 'pop',
    'active' => env('APP_THEME', 'pop'),

    // Each stylesheet implements the same token contract. Vite loads only the selected theme.
    'themes' => [
        'pop' => [
            'name' => 'Pop',
            'stylesheet' => 'resources/css/themes/pop.css',
            'assets' => 'resources/themes/pop',
        ],
        'future' => [
            'name' => 'Future',
            'stylesheet' => 'resources/css/themes/future.css',
            'assets' => 'resources/themes/future',
        ],
    ],
];
