<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MiningDataController;
use App\Http\Controllers\Api\ChartDataController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    // Redirect ke dashboard jika sudah login, ke login jika belum
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/export', [DashboardController::class, 'export'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.export');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Excel Upload (RAW DATA)
    |--------------------------------------------------------------------------
    */

    // Halaman upload Excel
    Route::get('/excel/upload', [ExcelImportController::class, 'index'])
        ->name('excel.upload.form');

    // Proses upload Excel → productions_raw
    Route::post('/excel/upload', [ExcelImportController::class, 'upload'])
        ->name('excel.upload.process');

    /*
    |--------------------------------------------------------------------------
    | Modul Mining Data - PT Semen Padang
    |--------------------------------------------------------------------------
    */
    Route::prefix('mining')->name('mining.')->group(function () {
        // Dashboard Mining
        Route::get('/dashboard', [MiningDataController::class, 'index'])
            ->name('dashboard');

        // Upload Excel
        Route::post('/upload', [MiningDataController::class, 'upload'])
            ->name('upload');

        // Delete Upload (Anti-Duplikasi Manual)
        Route::delete('/upload/{uploadId}', [MiningDataController::class, 'deleteUpload'])
            ->name('upload.delete');

        // API untuk Chart Data
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/dashboard-summary', [ChartDataController::class, 'dashboardSummary'])
                ->name('summary');
            Route::get('/daily-production', [ChartDataController::class, 'dailyProduction'])
                ->name('daily');
            Route::get('/weekly-production', [ChartDataController::class, 'weeklyProduction'])
                ->name('weekly');
            Route::get('/monthly-production', [ChartDataController::class, 'monthlyProduction'])
                ->name('monthly');
            Route::get('/equipment-stats', [ChartDataController::class, 'equipmentStats'])
                ->name('equipment');
            Route::get('/material-breakdown', [ChartDataController::class, 'materialBreakdown'])
                ->name('material');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze / Jetstream / dll)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

