<?php

use App\Http\Controllers\Admin\Post\CreateController as PostCreateController;
use App\Http\Controllers\Admin\Post\DeleteController as PostDeleteController;
use App\Http\Controllers\Admin\Post\EditController as PostEditController;
use App\Http\Controllers\Admin\Post\IndexController as PostIndexController;
use App\Http\Controllers\Admin\Post\ShowController as PostShowController;
use App\Http\Controllers\Admin\Post\StoreController as PostStoreController;
use App\Http\Controllers\Admin\Post\UpdateController as PostUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/post', PostIndexController::class)->name('admin.post.index');
    Route::delete('/post/{post}', PostDeleteController::class)->name('admin.post.delete');
    Route::get('/post/create', PostCreateController::class)->name('admin.post.create');
    Route::post('/post', PostStoreController::class)->name('admin.post.store');
    Route::get('/post/edit/{post}', PostEditController::class)->name('admin.post.edit');
    Route::patch('/post/{post}', PostUpdateController::class)->name('admin.post.update');
    Route::get('/post/{post}', PostShowController::class)->name('admin.post.show');
});
