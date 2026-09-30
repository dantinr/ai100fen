<?php

use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::get('/series', [FrontendController::class, 'index'])->name('series.index');
Route::get('/series/{slug}', [FrontendController::class, 'series'])->name('series.show');
Route::get('/series/{slug}/lessons/{lessonSlug}', [FrontendController::class, 'lesson'])->name('lessons.show');
Route::get('/series/{slug}/lessons/{lessonSlug}/checklist', [FrontendController::class, 'checklist'])->name('lessons.checklist');
Route::get('/pricing', [FrontendController::class, 'pricing'])->name('pricing');
Route::get('/live', [FrontendController::class, 'live'])->name('live');
Route::get('/me', [FrontendController::class, 'me'])->name('me');
Route::get('/login', [FrontendController::class, 'login'])->name('login');
Route::get('/register', [FrontendController::class, 'register'])->name('register');
