<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CoursePaymentController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\LessonPaymentController;
use App\Http\Controllers\LessonTestAttemptController;
use App\Http\Controllers\Post\ShowController as PublicPostShowController;
use App\Http\Controllers\SchoolRulePageController;
use App\Http\Controllers\TeacherPageController;
use App\Http\Controllers\TrialLessonRequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', IndexController::class)->name('index');

Route::get('/posts', fn () => redirect()->route('index'))->name('posts.index');
Route::get('/posts/{post}', PublicPostShowController::class)->name('posts.show');

Route::get('/rules', [SchoolRulePageController::class, 'index'])->name('rules.index');
Route::get('/teachers', [TeacherPageController::class, 'index'])->name('teachers.index');

Auth::routes(['verify' => true]);

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{course}/lessons/{lesson}', [CourseController::class, 'lesson'])
    ->name('courses.lessons.show');

Route::post('/courses/{course}/buy', [CoursePaymentController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('courses.buy');

Route::post('/lessons/{lesson}/buy', [LessonPaymentController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('lessons.buy');

Route::post('/courses/{course}/lessons/{lesson}/test', [LessonTestAttemptController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('courses.lessons.tests.submit');

Route::redirect('/payments', '/')->name('payments.index');
Route::redirect('/about', '/')->name('about.index');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::redirect('/home', '/');

Route::post('/trial-lesson-requests', [TrialLessonRequestController::class, 'store'])
    ->name('trial-lesson-requests.store');
