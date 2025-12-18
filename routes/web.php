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

// --- 👇 JURUS PAMUNGKAS: VERSI LENGKAP 100% SESUAI SQL 👇 ---
Route::get('/migrasi-darurat', function () {
    try {
        Artisan::call('config:clear');

        // 1. Matikan pengecekan relasi & hapus tabel lama agar bersih
        Schema::disableForeignKeyConstraints();
        $tables = ['riwayat_pinjam', 'kategori_buku', 'profile', 'buku', 'kategori', 'users', 'failed_jobs', 'migrations', 'password_resets', 'personal_access_tokens'];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        // 2. Buat Tabel USERS
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('isAdmin')->default(0);
            $table->timestamps();
        });

        // 3. Buat Tabel KATEGORI
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // 4. Buat Tabel BUKU
        Schema::create('buku', function (Blueprint $table) {
            $table->id();
            $table->string('kode_buku')->unique();
            $table->string('judul');
            $table->string('pengarang');
            $table->string('penerbit');
            $table->string('tahun_terbit');
            $table->text('deskripsi');
            $table->string('gambar')->nullable();
            $table->string('status')->default('In Stock');
            $table->timestamps();
        });

        // 5. Buat Tabel KATEGORI_BUKU (Relasi Buku ke Kategori)
        Schema::create('kategori_buku', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buku_id');
            $table->unsignedBigInteger('kategori_id');
            $table->foreign('buku_id')->references('id')->on('buku')->onDelete('cascade');
            $table->foreign('kategori_id')->references('id')->on('kategori')->onDelete('cascade');
            $table->timestamps();
        });

        // 6. Buat Tabel PROFILE
        Schema::create('profile', function (Blueprint $table) {
            $table->id();
            $table->string('npm')->unique();
            $table->string('prodi');
            $table->string('alamat');
            $table->string('noTelp');
            $table->string('photoProfile')->nullable();
            $table->unsignedBigInteger('users_id');
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 7. Buat Tabel RIWAYAT_PINJAM
        Schema::create('riwayat_pinjam', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('buku_id');
            $table->date('tanggal_pinjam');
            $table->date('tanggal_wajib_kembali');
            $table->date('tanggal_pengembalian')->nullable();
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('buku_id')->references('id')->on('buku')->onDelete('cascade');
            $table->timestamps();
        });

        // 8. Isi data Admin & Kategori awal agar tidak kosong
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'isAdmin'  => 1
        ]);

        DB::table('profile')->insert([
            'npm' => 'admin', 
            'prodi' => 'Admin Sistem', 
            'alamat' => 'Kampus', 
            'noTelp' => '0812345', 
            'users_id' => $admin->id, 
            'created_at' => now()
        ]);

        DB::table('kategori')->insert([
            ['nama' => 'Novel', 'created_at' => now()],
            ['nama' => 'Pelajaran', 'created_at' => now()],
            ['nama' => 'Pemrograman', 'created_at' => now()]
        ]);

        // 9. Isi data Buku awal (Contoh dari SQL kamu)
        DB::table('buku')->insert([
            [
                'kode_buku' => 'LSK-01',
                'judul' => 'Laskar Pelangi',
                'pengarang' => 'Andrea Hirata',
                'penerbit' => 'Bentang Pustaka',
                'tahun_terbit' => '2005',
                'deskripsi' => 'Kisah inspiratif anak-anak Belitung.',
                'status' => 'In Stock',
                'created_at' => now()
            ],
            [
                'kode_buku' => 'HJN-01',
                'judul' => 'Hujan',
                'pengarang' => 'Tere Liye',
                'penerbit' => 'Gramedia Pustaka',
                'tahun_terbit' => '2016',
                'deskripsi' => 'Kisah tentang persahabatan dan perpisahan.',
                'status' => 'In Stock',
                'created_at' => now()
            ]
        ]);
        
        return '<h1>✅ BERHASIL TOTAL & STRUKTUR LENGKAP!</h1> 
                <p>Semua tabel dan data awal berhasil dipasang di Neon.</p>
                <hr>
                <a href="/" style="font-size: 20px; font-weight: bold; background: #28a745; color: white; padding: 12px; text-decoration: none; border-radius: 5px; display: inline-block;">➡️ LOGIN SEKARANG</a>';

    } catch (\Exception $e) {
        return '<h1 style="color:red">❌ Gagal Lagi!</h1><p>' . $e->getMessage() . '</p>';
    }
});