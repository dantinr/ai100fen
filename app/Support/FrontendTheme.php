<?php

namespace App\Support;

use App\Services\ThemeConfiguration;

class FrontendTheme
{
    public static function current(): array
    {
        $themes = config('themes.themes');
        $override = app(ThemeConfiguration::class)->selected();
        $active = $override ?? config('themes.active');
        $default = config('themes.default');
        $default = is_string($default) && isset($themes[$default]) ? $default : 'pop';
        $id = is_string($active) && isset($themes[$active]) ? $active : $default;

        return ['id' => $id, ...$themes[$id]];
    }

    public static function pageVariant(): string
    {
        return match (true) {
            request()->routeIs('home') => 'home',
            request()->routeIs('questions', 'questions.*') => 'question-pool',
            request()->routeIs('lessons.*', 'free.lesson', 'free.progress', 'courses.preview') => 'lesson',
            request()->routeIs('free.index') => 'series',
            request()->routeIs('series.*', 'courses.show') => 'series',
            request()->routeIs('login', 'register') => 'account',
            request()->routeIs('pricing') => 'checkout',
            request()->routeIs('me') => 'progress',
            request()->routeIs('live') => 'live',
            default => 'default',
        };
    }
}
