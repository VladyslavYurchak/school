<?php

use App\Http\Controllers\Admin\SocialPublishing\CreateController;
use App\Http\Controllers\Admin\SocialPublishing\DeleteController;
use App\Http\Controllers\Admin\SocialPublishing\EditController;
use App\Http\Controllers\Admin\SocialPublishing\IndexController;
use App\Http\Controllers\Admin\SocialPublishing\PublishController;
use App\Http\Controllers\Admin\SocialPublishing\StoreController;
use App\Http\Controllers\Admin\SocialPublishing\UpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/social-publishing')
    ->middleware('admin')
    ->name('admin.social-publishing.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/', StoreController::class)->name('store');
        Route::get('/{publication}/edit', EditController::class)->name('edit');
        Route::patch('/{publication}', UpdateController::class)->name('update');
        Route::post('/{publication}/publish', PublishController::class)->name('publish');
        Route::delete('/{publication}', DeleteController::class)->name('delete');
    });
