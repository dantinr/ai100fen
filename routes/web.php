<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::get('/series', [FrontendController::class, 'index'])->name('series.index');
Route::get('/series/{slug}', [FrontendController::class, 'series'])->name('series.show');
Route::get('/series/{slug}/lessons/{lessonSlug}', [FrontendController::class, 'lesson'])->name('lessons.show');
Route::get('/series/{slug}/lessons/{lessonSlug}/checklist', [FrontendController::class, 'checklist'])->name('lessons.checklist');
Route::get('/pricing', [FrontendController::class, 'pricing'])->name('pricing');
Route::get('/live', [FrontendController::class, 'live'])->name('live');
Route::view('/questions', 'frontend.questions')->name('questions');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:30,1,login:')->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->middleware('throttle:6,1,register:')->name('register.store');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/me', [ProfileController::class, 'show'])->name('me');
    Route::patch('/me/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/me/password', [ProfileController::class, 'password'])->middleware('throttle:6,1,password:')->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
