<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
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

// --- 👇 JURUS PAMUNGKAS: RESET + FIX STRUKTUR SQL 👇 ---
Route::get('/migrasi-darurat', function () {
    try {
        // 1. Bersihkan Cache agar settingan terbaru terbaca
        Artisan::call('config:clear');
        
        // 2. Jalankan migrasi standar (Membuat tabel users, buku, kategori, dll)
        Artisan::call('migrate:fresh', ['--force' => true]);

        // 3. Tambahkan kolom isAdmin ke tabel users (Sesuai SQL kamu)
        if (Schema::hasTable('users')) {
            if (!Schema::hasColumn('users', 'isAdmin')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->tinyInteger('isAdmin')->default(0);
                });
            }
        }

        // 4. Buat Tabel Profile (Sesuai kolom di SQL kamu: npm, prodi, alamat, noTelp, users_id)
        if (!Schema::hasTable('profile')) {
            Schema::create('profile', function (Blueprint $table) {
                $table->id();
                $table->string('npm')->unique();
                $table->string('prodi');
                $table->string('alamat');
                $table->string('noTelp'); 
                $table->string('photoProfile')->nullable();
                $table->unsignedBigInteger('users_id'); // Menggunakan users_id sesuai file SQL
                $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 5. Buat Akun Admin Default
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password123'),
                'isAdmin'  => 1
            ]
        );

        // 6. Buat Data Profile Admin (Agar saat login tidak error mencari relasi profile)
        DB::table('profile')->updateOrInsert(
            ['users_id' => $admin->id],
            [
                'npm'          => 'admin',
                'prodi'        => 'Admin Sistem',
                'alamat'       => 'Perpustakaan Digital',
                'noTelp'       => '08123456789',
                'photoProfile' => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );
        
        return '<h1>✅ BERHASIL TOTAL!</h1> 
                <p>Database telah di-reset dan disesuaikan dengan struktur SQL kamu.</p>
                <p>Akun Login: <b>admin@gmail.com</b> / Password: <b>password123</b></p>
                <hr>
                <a href="/" style="font-size: 20px; font-weight: bold; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">➡️ KE HALAMAN LOGIN</a>';

    } catch (\Exception $e) {
        return '<h1 style="color:red">❌ Gagal Lagi!</h1>
                <p>Pesan Error: ' . $e->getMessage() . '</p>
                <p><i>Pastikan file migrations di folder database/migrations sudah benar.</i></p>';
    }
});