<?php

use App\Http\Controllers\Admin\SchoolRules\CreateController as AdminSchoolRuleCreateController;
use App\Http\Controllers\Admin\SchoolRules\DestroyController as AdminSchoolRuleDestroyController;
use App\Http\Controllers\Admin\SchoolRules\EditController as AdminSchoolRuleEditController;
use App\Http\Controllers\Admin\SchoolRules\IndexController as AdminSchoolRuleIndexController;
use App\Http\Controllers\Admin\SchoolRules\StoreController as AdminSchoolRuleStoreController;
use App\Http\Controllers\Admin\SchoolRules\UpdateController as AdminSchoolRuleUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/school-rules')->name('admin.school-rules.')->middleware(['admin'])->group(function () {
    Route::get('/', AdminSchoolRuleIndexController::class)->name('index');
    Route::get('/create', AdminSchoolRuleCreateController::class)->name('create');
    Route::post('/', AdminSchoolRuleStoreController::class)->name('store');
    Route::get('/{schoolRule}/edit', AdminSchoolRuleEditController::class)->name('edit');
    Route::put('/{schoolRule}', AdminSchoolRuleUpdateController::class)->name('update');
    Route::delete('/{schoolRule}', AdminSchoolRuleDestroyController::class)->name('destroy');
});
