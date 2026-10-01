<?php

namespace App\Support;

class FrontendTheme
{
    public static function current(): array
    {
        $themes = config('themes.themes');
        $active = config('themes.active');
        $default = config('themes.default');
        $default = is_string($default) && isset($themes[$default]) ? $default : 'pop';
        $id = is_string($active) && isset($themes[$active]) ? $active : $default;

        return ['id' => $id, ...$themes[$id]];
    }

    public static function pageVariant(): string
    {
        return match (true) {
            request()->routeIs('home') => 'home',
            request()->routeIs('questions') => 'question-pool',
            request()->routeIs('lessons.*', 'free.lesson', 'free.progress') => 'lesson',
            request()->routeIs('free.index') => 'series',
            request()->routeIs('series.*') => 'series',
            request()->routeIs('login', 'register') => 'account',
            request()->routeIs('pricing') => 'checkout',
            request()->routeIs('me') => 'progress',
            request()->routeIs('live') => 'live',
            default => 'default',
        };
    }
}
