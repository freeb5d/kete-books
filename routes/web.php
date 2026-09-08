<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Bilingual routes: en/mi
|--------------------------------------------------------------------------
| Every route below is automatically available at both
|   /en/...   and   /mi/...
| via mcamara/laravel-localization's group middleware.
| e.g. /mi/putunga-moni (dashboard) and /en/dashboard both resolve here.
*/
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', DashboardController::class.'@index')->name('dashboard');

        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');

        Route::resource('invoices', InvoiceController::class)->except(['edit', 'update', 'destroy']);
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    });
});

require __DIR__.'/auth.php';
