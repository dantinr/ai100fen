<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommitHistoryController;
use App\Http\Controllers\CoursePreviewController;
use App\Http\Controllers\FreeLabController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LessonNoteController;
use App\Http\Controllers\LiveEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCourseController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionTopicsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::view('/about', 'frontend.about')->name('about');
Route::get('/lab', [FreeLabController::class, 'index'])->name('free.index');
Route::get('/lab/{series}/{lessonSlug}', [FreeLabController::class, 'show'])->name('free.lesson');
Route::get('/lab/{series}/{lessonSlug}/resources/{resource}', [FreeLabController::class, 'download'])->name('free.resource');
Route::get('/series', [FrontendController::class, 'index'])->name('series.index');
Route::get('/courses/{series}', PublicCourseController::class)->name('courses.show');
Route::get('/series/{slug}', [FrontendController::class, 'series'])->name('series.show');
Route::get('/series/{slug}/lessons/{lessonSlug}', [FreeLabController::class, 'showCourse'])->name('lessons.show');
Route::get('/series/{slug}/lessons/{lessonSlug}/checklist', [FreeLabController::class, 'checklistCourse'])->name('lessons.checklist');
Route::get('/series/{slug}/lessons/{lessonSlug}/resources/{resource}', [FreeLabController::class, 'downloadCourse'])->name('lessons.resource');
Route::get('/pricing', [FrontendController::class, 'pricing'])->name('pricing');
Route::get('/live', [FrontendController::class, 'live'])->name('live');
Route::get('/live/{session:slug}/enter', [LiveEntryController::class, 'enter'])->name('live.enter');
Route::get('/live/{session:slug}/replay', [LiveEntryController::class, 'replay'])->name('live.replay');
Route::get('/questions', QuestionTopicsController::class)->name('questions');
Route::post('/questions/recommendations', [QuestionController::class, 'recommend'])->middleware('throttle:20,1,question-recommend:')->name('questions.recommend');
Route::get('/commits', CommitHistoryController::class)->name('commits');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:30,1,login:')->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->middleware('throttle:6,1,register:')->name('register.store');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::post('/questions', [QuestionController::class, 'store'])->middleware('throttle:10,1,question-submit:')->name('questions.store');
    Route::get('/questions/mine', [QuestionController::class, 'index'])->name('questions.mine');
    Route::get('/questions/{question}', [QuestionController::class, 'show'])->whereNumber('question')->name('questions.show');
    Route::get('/preview/courses/{series}/{lessonSlug?}', CoursePreviewController::class)->name('courses.preview');
    Route::post('/lab/{series}/{lessonSlug}/progress', [FreeLabController::class, 'save'])->middleware('throttle:60,1,free-progress:')->name('free.progress');
    Route::post('/series/{slug}/lessons/{lessonSlug}/progress', [FreeLabController::class, 'saveCourse'])->middleware('throttle:60,1,free-progress:')->name('lessons.progress');
    Route::put('/series/{series}/lessons/{lessonSlug}/notes', LessonNoteController::class)->middleware('throttle:30,1,lesson-note:')->name('lessons.notes');
    Route::get('/me', [ProfileController::class, 'show'])->name('me');
    Route::get('/me/learning/{progress}', [ProfileController::class, 'history'])->whereNumber('progress')->name('learning.history');
    Route::patch('/me/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/me/password', [ProfileController::class, 'password'])->middleware('throttle:6,1,password:')->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
