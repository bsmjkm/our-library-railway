<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan; // Wajib ada
use App\Http\Controllers\BukuController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CetakLaporanController;
use App\Http\Controllers\PengembalianController;
use App\Http\Controllers\RiwayatPinjamController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index']);

    Route::resource('kategori', KategoriController::class);

    Route::resource('buku', BukuController::class);

    Route::resource('anggota', AnggotaController::class);

    Route::resource('profile', ProfileController::class)->only('index','update','edit');

    Route::resource('peminjaman', RiwayatPinjamController::class);

    Route::get('/cetaklaporan', CetakLaporanController::class);

    Route::get('/pengembalian', [PengembalianController::class,'index']);

    Route::post('/pengembalian', [PengembalianController::class,'pengembalian']);

});

// --- 👇 JURUS DARURAT MIGRATION + SEEDER (PAKET LENGKAP) 👇 ---
Route::get('/migrasi-darurat', function () {
    try {
        // 1. Bersihkan Cache
        Artisan::call('config:clear');
        
        // 2. RESET DATABASE (Hapus semua & Buat Ulang)
        Artisan::call('migrate:fresh', ['--force' => true]);
        
        // 3. ISI DATA (Jalankan Seeder) <-- INI SUDAH SAYA AKTIFKAN
        Artisan::call('db:seed', ['--force' => true]); 
        
        return '<h1>✅ SUKSES RESET & SEEDING!</h1> 
                <p>Tabel sudah dibuat ulang DAN Data awal (Seeder) sudah diisi.</p> 
                <hr>
                <p><b>Output System:</b></p>
                <pre>' . Artisan::output() . '</pre>
                <br> 
                <a href="/" style="font-size: 20px; font-weight: bold; background: green; color: white; padding: 10px; text-decoration: none;">➡️ KLIK DISINI UNTUK LOGIN</a>';
    } catch (\Exception $e) {
        return '<h1>❌ Gagal!</h1><p>' . $e->getMessage() . '</p>';
    }
});