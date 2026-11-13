
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\PaymentBehaviorController;
use App\Http\Controllers\ServiceUsageController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// =====================================================
// HALAMAN AWAL
// =====================================================

Route::get('/', fn () => redirect()->route('dashboard'));

// =====================================================
// HANYA BISA DIAKSES KALAU BELUM LOGIN
// =====================================================

Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.process');

});

// =====================================================
// HANYA BISA DIAKSES KALAU SUDAH LOGIN
// =====================================================

Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // =================================================
    // DASHBOARD
    // =================================================

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // =================================================
    // PELANGGAN
    // =================================================

    // Daftar pelanggan
    Route::get('/pelanggan', [CustomerController::class, 'index'])
        ->name('customers.index');

    // Form tambah pelanggan
    Route::get('/pelanggan/tambah', [CustomerController::class, 'create'])
        ->name('customers.create');

    // Simpan pelanggan baru
    Route::post('/pelanggan', [CustomerController::class, 'store'])
        ->name('customers.store');

    // Import CSV
    Route::post('/pelanggan/import', [CustomerController::class, 'import'])
        ->name('customers.import');

    // Import CSV riwayat pembayaran
    Route::post('/pelanggan/import-pembayaran', [CustomerController::class, 'importPayments'])
        ->name('customers.import-payments');

    // Detail pelanggan
    Route::get('/pelanggan/{customer}', [CustomerController::class, 'show'])
        ->whereNumber('customer')
        ->name('customers.show');

    // =================================================
    // PERILAKU PEMBAYARAN
    // =================================================

    Route::get('/perilaku-pembayaran', [PaymentBehaviorController::class, 'index'])
        ->name('payments.behavior');

    // =================================================
    // PENGGUNAAN LAYANAN
    // =================================================

    Route::get('/penggunaan-layanan', [ServiceUsageController::class, 'index'])
        ->name('service-usage.index');

    Route::get('/penggunaan-layanan/export', [ServiceUsageController::class, 'export'])
        ->name('service-usage.export');

    // =================================================
    // INSIGHT & REKOMENDASI
    // =================================================

    // Halaman utama insight
    Route::get('/insight-rekomendasi', [InsightController::class, 'index'])
        ->name('insights.index');

    // Rekomendasi lengkap
    Route::get('/insight/rekomendasi', [InsightController::class, 'full'])
        ->name('insights.full');

    // Detail rekomendasi berdasarkan kategori
    Route::get('/insight/rekomendasi/{kategori}', [InsightController::class, 'detail'])
        ->name('insights.detail');

    // =================================================
    // NOTIFIKASI
    // =================================================

    Route::get('/notifikasi', [NotificationController::class, 'index'])
        ->name('notifications.index');

});
