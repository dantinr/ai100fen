<?php

namespace App\Providers;

use App\Support\FrontendTheme;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.frontend', function (\Illuminate\View\View $view): void {
            $view->with('theme', FrontendTheme::current());
            $view->with('pageVariant', FrontendTheme::pageVariant());
        });
    }
}
