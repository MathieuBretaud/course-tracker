<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('receipts')->name('receipts.')->group(function () {

        Route::get('index', [ReceiptController::class, 'index'])
            ->name('index');
        Route::get('{receipt}', [ReceiptController::class, 'show'])
            ->name('show');
        Route::post('store', [ReceiptController::class, 'store'])
            ->name('store');
    });
});

require __DIR__.'/settings.php';
