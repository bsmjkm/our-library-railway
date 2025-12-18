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

// --- 👇 JURUS ANTI-GAGAL: BUAT TABEL MANUAL SATU PER SATU 👇 ---
Route::get('/migrasi-darurat', function () {
    try {
        Artisan::call('config:clear');

        // 1. Hapus tabel lama jika ada agar bersih (Urutan hapus harus benar karena ada relasi)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('profile');
        Schema::dropIfExists('riwayat_pinjam');
        Schema::dropIfExists('kategori_buku');
        Schema::dropIfExists('buku');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        // 2. Buat Tabel USERS secara manual (Pondasi Utama)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('isAdmin')->default(0); //
            $table->timestamps();
        });

        // 3. Jalankan Migrasi Sisanya (Buku, Kategori, dll)
        // Kita tidak pakai migrate:fresh karena tabel users sudah kita buat manual di atas
        Artisan::call('migrate', ['--force' => true]);

        // 4. Buat Tabel PROFILE secara manual (Karena sering error di migration)
        if (!Schema::hasTable('profile')) {
            Schema::create('profile', function (Blueprint $table) {
                $table->id();
                $table->string('npm')->unique(); //
                $table->string('prodi'); //
                $table->string('alamat'); //
                $table->string('noTelp'); //
                $table->string('photoProfile')->nullable(); //
                $table->unsignedBigInteger('users_id'); //
                $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 5. Buat Akun Admin
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'isAdmin'  => 1 //
        ]);

        // 6. Buat Profile Admin
        DB::table('profile')->insert([
            'npm'          => 'admin',
            'prodi'        => 'Admin Sistem',
            'alamat'       => 'Perpustakaan Digital',
            'noTelp'       => '08123456789',
            'users_id'     => $admin->id, //
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
        
        return '<h1>✅ BERHASIL TOTAL!</h1> 
                <p>Tabel Users & Profile dibuat manual untuk menghindari error relasi.</p>
                <p>Login: <b>admin@gmail.com</b> / <b>password123</b></p>
                <hr>
                <a href="/" style="font-size: 20px; font-weight: bold; background: #28a745; color: white; padding: 12px; text-decoration: none;">➡️ LOGIN SEKARANG</a>';

    } catch (\Exception $e) {
        return '<h1 style="color:red">❌ Gagal Lagi!</h1>
                <p>Pesan Error: ' . $e->getMessage() . '</p>';
    }
});