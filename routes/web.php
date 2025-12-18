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

// --- 👇 JURUS FINAL: SESUAI SQL ASLI 👇 ---
Route::get('/migrasi-darurat', function () {
    try {
        Artisan::call('config:clear');
        
        // 1. Reset Database (Hapus Semua)
        Artisan::call('migrate:fresh', ['--force' => true]);

        // 2. 🛠️ PASTIKAN KOLOM 'isAdmin' DI TABEL USERS ADA
        if (!Schema::hasColumn('users', 'isAdmin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->tinyInteger('isAdmin')->default(0);
            });
        }

        // 3. 🛠️ BANGUN TABEL PROFILE (PERSIS SESUAI SQL KAMU)
        if (!Schema::hasTable('profile')) {
            Schema::create('profile', function (Blueprint $table) {
                $table->id();
                $table->string('npm')->unique();
                $table->string('prodi');       // <-- Wajib ada
                $table->string('alamat');
                $table->string('noTelp');      // <-- Perhatikan huruf besar T
                $table->string('photoProfile')->nullable();
                $table->unsignedBigInteger('users_id'); // <-- PENTING: users_id (bukan user_id)
                $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 4. Buat Akun Admin (Sesuai SQL)
        // Cek dulu apakah user admin sudah ada, kalau belum buat baru
        $admin = User::where('email', 'admin@gmail.com')->first();
        if (!$admin) {
            $admin = User::forceCreate([
                'name'     => 'Admin',
                'email'    => 'admin@gmail.com',
                'password' => Hash::make('password123'), // Password default
                'isAdmin'  => 1
            ]);
        }

        // 5. Buat Profile Admin (Wajib biar tidak error saat login)
        // Cek apakah profile untuk admin ini sudah ada
        $cekProfile = DB::table('profile')->where('users_id', $admin->id)->first();
        
        if (!$cekProfile) {
            DB::table('profile')->insert([
                'npm'          => 'admin',
                'prodi'        => 'Sistem Informasi',
                'alamat'       => 'Ruang Admin',
                'noTelp'       => '08123456789',
                'photoProfile' => 'default.jpg',
                'users_id'     => $admin->id,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
        
        return '<h1>✅ SUKSES FULL (SESUAI SQL)!</h1> 
                <p>Tabel Profile berhasil dibuat dengan struktur: users_id, prodi, noTelp, dll.</p>
                <p>Akun Admin Siap: <b>admin@gmail.com</b> / <b>password123</b></p>
                <hr>
                <a href="/" style="font-size: 20px; font-weight: bold; background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">➡️ LOGIN SEKARANG</a>';

    } catch (\Exception $e) {
        return '<h1 style="color:red">❌ Gagal!</h1><p>' . $e->getMessage() . '</p>';
    }
});