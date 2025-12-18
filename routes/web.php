<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan; // Pastikan baris ini ada
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

// --- 👇 JURUS DARURAT MIGRATION (VERSI RESET TOTAL) 👇 ---
// Link ini akan MENGHAPUS semua tabel dan MEMBUAT ULANG dari nol.
Route::get('/migrasi-darurat', function () {
    try {
        // 1. Bersihkan cache config agar tidak nyangkut
        Artisan::call('config:clear');
        
        // 2. Jalankan migrate:fresh (Hapus semua tabel & buat ulang)
        Artisan::call('migrate:fresh', ['--force' => true]);
        
        // 3. (Opsional) Jika kamu punya seeder, aktifkan baris di bawah ini:
        // Artisan::call('db:seed', ['--force' => true]);

        return '<h1>✅ SUKSES RESET DATABASE!</h1> 
                <p>Database berhasil di-reset total. Tabel users, buku, dll sudah dibuat ulang.</p> 
                <hr>
                <pre>' . Artisan::output() . '</pre>
                <br> 
                <a href="/register" style="font-size: 20px; font-weight: bold;">➡️ KLIK DISINI UNTUK REGISTER</a>';
    } catch (\Exception $e) {
        return '❌ Gagal: ' . $e->getMessage();
    }
});