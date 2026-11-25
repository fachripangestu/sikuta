<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminKunjunganController;
use App\Http\Controllers\GuestFormController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GuestFormController::class, 'index'])->name('guest.form');
Route::post('/guest-submit', [GuestFormController::class, 'store'])->name('guest.form.submit');
Route::get('/guest/kunjungan/{tamu}/surat-balasan/raw', [GuestFormController::class, 'balasanRaw'])->name('guest.kunjungan.balasan.raw');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('admin')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::middleware('admin:data_entry')->get('/admin/dashboard/data-entry', [AdminDashboardController::class, 'dataEntry'])->name('admin.dashboard.data_entry');
    Route::middleware('admin:sekretaris_pimpinan')->get('/admin/dashboard/sekretaris-pimpinan', [AdminDashboardController::class, 'sekretarisPimpinan'])->name('admin.dashboard.sekretaris_pimpinan');
    Route::middleware('admin:bidang_tujuan')->get('/admin/dashboard/operator-bidang', [AdminDashboardController::class, 'bidangTujuan'])->name('admin.dashboard.bidang_tujuan');
    Route::middleware('admin:subbag_umum')->get('/admin/dashboard/subbag-umum', [AdminDashboardController::class, 'subbagUmum'])->name('admin.dashboard.subbag_umum');

    Route::post('/admin/kunjungan/{tamu}/verifikasi', [AdminKunjunganController::class, 'verify'])->name('admin.kunjungan.verify');
    Route::post('/admin/kunjungan/{tamu}/upload-balasan', [AdminKunjunganController::class, 'uploadBalasan'])->middleware('admin:subbag_umum')->name('admin.kunjungan.upload_balasan');
    Route::post('/admin/kunjungan/{tamu}/hapus-balasan', [AdminKunjunganController::class, 'deleteBalasan'])->middleware('admin:subbag_umum')->name('admin.kunjungan.delete_balasan');
    Route::get('/admin/kunjungan/{tamu}/lampiran', [AdminKunjunganController::class, 'lampiran'])->name('admin.kunjungan.lampiran');
    Route::get('/admin/kunjungan/{tamu}/lampiran/raw', [AdminKunjunganController::class, 'lampiranRaw'])->name('admin.kunjungan.lampiran.raw');
    Route::get('/admin/kunjungan/{tamu}/balasan', [AdminKunjunganController::class, 'balasan'])->name('admin.kunjungan.balasan');
    Route::get('/admin/kunjungan/{tamu}/balasan/raw', [AdminKunjunganController::class, 'balasanRaw'])->name('admin.kunjungan.balasan.raw');
    Route::get('/admin/notifikasi/live', [AdminDashboardController::class, 'notificationsLive'])->name('admin.notifications.live');

    Route::middleware('admin:admin')->group(function () {
        Route::get('/admin/accounts', [AdminAccountController::class, 'accounts'])->name('admin.accounts');
        Route::post('/admin/accounts', [AdminAccountController::class, 'storeAccount'])->name('admin.accounts.store');
        Route::post('/admin/accounts/{user}/toggle-status', [AdminAccountController::class, 'toggleAccountStatus'])->name('admin.accounts.toggle_status');
        Route::post('/admin/accounts/{user}/reset-password', [AdminAccountController::class, 'resetAccountPassword'])->name('admin.accounts.reset_password');
        Route::post('/admin/accounts/{user}/delete', [AdminAccountController::class, 'deleteAccount'])->name('admin.accounts.delete');
    });

    Route::get('/admin/profile', [AdminAccountController::class, 'profile'])->name('admin.profile');
    Route::post('/admin/profile', [AdminAccountController::class, 'updateProfile'])->name('admin.profile.update');
});
