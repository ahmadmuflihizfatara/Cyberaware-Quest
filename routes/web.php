<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\InstrumenController;
use App\Http\Controllers\Admin\KegiatanController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FasilitatorController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\PublikController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- area publik

Route::get('/', [PublikController::class, 'beranda'])->name('beranda');
Route::get('/program', [PublikController::class, 'program'])->name('program.index');
Route::get('/program/{program}', [PublikController::class, 'programShow'])->name('program.show');
Route::get('/kegiatan', [PublikController::class, 'kegiatan'])->name('kegiatan.index');
Route::get('/kegiatan/{kegiatan}', [PublikController::class, 'kegiatanShow'])->name('kegiatan.show');
Route::get('/verifikasi-sertifikat', [PublikController::class, 'verifikasi'])->name('verifikasi');
Route::view('/kebijakan-privasi', 'publik.kebijakan-privasi')->name('kebijakan-privasi');

// ----------------------------------------------------------------- autentikasi

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'formLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/registrasi', [AuthController::class, 'formRegistrasi'])->name('registrasi');
    Route::post('/registrasi', [AuthController::class, 'registrasi']);
    Route::view('/lupa-sandi', 'auth.lupa-sandi')->name('lupa-sandi');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------- area peserta

Route::middleware(['auth', 'role:peserta'])->prefix('peserta')->name('peserta.')->group(function () {
    Route::get('/dashboard', [PesertaController::class, 'dashboard'])->name('dashboard');
    Route::get('/kegiatan-saya', [PesertaController::class, 'kegiatanSaya'])->name('kegiatan');
    Route::get('/poin', [PesertaController::class, 'poin'])->name('poin');
    Route::get('/reward', [PesertaController::class, 'reward'])->name('reward');
    Route::post('/reward/{reward}/tukar', [PesertaController::class, 'tukarReward'])->name('reward.tukar');
    Route::get('/badge', [PesertaController::class, 'badge'])->name('badge');
    Route::get('/profil', [PesertaController::class, 'profil'])->name('profil');
    Route::post('/profil', [PesertaController::class, 'simpanProfil'])->name('profil.simpan');

    Route::get('/informasi-kegiatan', [PesertaController::class, 'informasiKegiatan'])->name('informasi-kegiatan');
    Route::get('/informasi-kegiatan/{kegiatan}', [PesertaController::class, 'informasiKegiatanShow'])->name('informasi-kegiatan.show');
    Route::post('/informasi-kegiatan/{kegiatan}/daftar', [PesertaController::class, 'daftar'])->name('informasi-kegiatan.daftar');

    Route::prefix('pendaftaran/{pendaftaran}')->group(function () {
        Route::get('/', [PesertaController::class, 'show'])->name('pendaftaran.show');
        Route::get('/persetujuan', [PesertaController::class, 'persetujuan'])->name('persetujuan');
        Route::post('/persetujuan', [PesertaController::class, 'simpanPersetujuan'])->name('persetujuan.simpan');
        Route::get('/instrumen/{fase}', [PesertaController::class, 'instrumen'])->name('instrumen');
        Route::post('/instrumen/{fase}', [PesertaController::class, 'kirimInstrumen'])->name('instrumen.kirim');
        Route::get('/checkin', [PesertaController::class, 'checkin'])->name('checkin');
        Route::post('/checkin', [PesertaController::class, 'simpanCheckin'])->name('checkin.simpan');
        Route::get('/materi', [PesertaController::class, 'materi'])->name('materi');
        Route::get('/aktivitas', [PesertaController::class, 'aktivitas'])->name('aktivitas');
        Route::post('/aktivitas/{aktivitas}/artefak', [PesertaController::class, 'simpanArtefak'])->name('artefak.simpan');
        Route::get('/gamifikasi', [PesertaController::class, 'gamifikasi'])->name('gamifikasi');
        Route::post('/gamifikasi/{gamifikasi}', [PesertaController::class, 'ikutGamifikasi'])->name('gamifikasi.ikut');
        Route::get('/leaderboard', [PesertaController::class, 'leaderboardHalaman'])->name('leaderboard');
        Route::get('/sertifikat', [PesertaController::class, 'sertifikat'])->name('sertifikat');
    });
});

// ------------------------------------------------------------ area fasilitator

Route::middleware(['auth', 'role:fasilitator'])->prefix('fasilitator')->name('fasilitator.')->group(function () {
    Route::get('/dashboard', [FasilitatorController::class, 'dashboard'])->name('dashboard');
    Route::get('/kegiatan', [FasilitatorController::class, 'kegiatan'])->name('kegiatan');
    Route::get('/kegiatan/{kegiatan}', [FasilitatorController::class, 'kegiatanShow'])->name('kegiatan.show');
    Route::get('/sesi/{sesi}/qr', [FasilitatorController::class, 'qr'])->name('sesi.qr');
    Route::post('/sesi/{sesi}/qr', [FasilitatorController::class, 'buatToken'])->name('sesi.qr.buat');
    Route::get('/sesi/{sesi}/kehadiran', [FasilitatorController::class, 'kehadiran'])->name('sesi.kehadiran');
    Route::post('/sesi/{sesi}/kehadiran', [FasilitatorController::class, 'hadirManual'])->name('sesi.kehadiran.manual');
    Route::get('/sesi/{sesi}/aktivitas', [FasilitatorController::class, 'aktivitas'])->name('sesi.aktivitas');
    Route::post('/sesi/{sesi}/aktivitas', [FasilitatorController::class, 'simpanAktivitas'])->name('sesi.aktivitas.simpan');
    Route::post('/sesi/{sesi}/gamifikasi', [FasilitatorController::class, 'simpanGamifikasi'])->name('sesi.gamifikasi.simpan');
    Route::post('/gamifikasi/{gamifikasi}/nilai', [FasilitatorController::class, 'nilaiGamifikasi'])->name('gamifikasi.nilai');
    Route::get('/artefak', [FasilitatorController::class, 'artefak'])->name('artefak');
    Route::post('/artefak/{artefak}', [FasilitatorController::class, 'verifikasiArtefak'])->name('artefak.verifikasi');
    Route::get('/rekap', [FasilitatorController::class, 'rekap'])->name('rekap');
});

// ------------------------------------------------------------------ area admin

Route::middleware(['auth', 'role:admin,penyelenggara'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Data master (satu controller generik, lihat MasterController::definisi()).
    Route::get('/master/{resource}', [MasterController::class, 'index'])->name('master.index');
    Route::post('/master/{resource}', [MasterController::class, 'store'])->name('master.store');
    Route::put('/master/{resource}/{id}', [MasterController::class, 'update'])->name('master.update');
    Route::delete('/master/{resource}/{id}', [MasterController::class, 'destroy'])->name('master.destroy');

    // Kegiatan dan turunannya.
    Route::get('/kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');
    Route::post('/kegiatan', [KegiatanController::class, 'store'])->name('kegiatan.store');
    Route::put('/kegiatan/{kegiatan}', [KegiatanController::class, 'update'])->name('kegiatan.update');
    Route::get('/kegiatan/{kegiatan}', [KegiatanController::class, 'show'])->name('kegiatan.show');
    Route::get('/kegiatan/{kegiatan}/demografi', [KegiatanController::class, 'demografi'])->name('kegiatan.demografi');
    Route::post('/kegiatan/{kegiatan}/sesi', [KegiatanController::class, 'simpanSesi'])->name('kegiatan.sesi');
    Route::delete('/sesi/{sesi}', [KegiatanController::class, 'hapusSesi'])->name('sesi.hapus');
    Route::post('/sesi/{sesi}/token', [KegiatanController::class, 'buatTokenSesi'])->name('sesi.token.buat');
    Route::post('/kegiatan/{kegiatan}/fasilitator', [KegiatanController::class, 'tugaskanFasilitator'])->name('kegiatan.fasilitator');
    Route::delete('/kegiatan/{kegiatan}/fasilitator/{fasilitator}', [KegiatanController::class, 'lepasFasilitator'])->name('kegiatan.fasilitator.lepas');
    Route::post('/kegiatan/{kegiatan}/pelaksanaan', [KegiatanController::class, 'simpanPelaksanaan'])->name('kegiatan.pelaksanaan');
    Route::post('/kegiatan/{kegiatan}/evaluasi', [KegiatanController::class, 'simpanEvaluasi'])->name('kegiatan.evaluasi');
    Route::post('/kegiatan/{kegiatan}/indikator', [KegiatanController::class, 'simpanIndikator'])->name('kegiatan.indikator');
    Route::post('/kegiatan/{kegiatan}/hasil/{fase}/toggle', [KegiatanController::class, 'toggleTampilkanHasil'])->name('kegiatan.hasil.toggle');
    Route::post('/kegiatan/{kegiatan}/sertifikat/terbitkan', [KegiatanController::class, 'terbitkanSertifikatMassal'])->name('kegiatan.sertifikat.terbitkan');
    Route::put('/pendaftaran/{pendaftaran}', [KegiatanController::class, 'ubahStatusPendaftaran'])->name('pendaftaran.status');
    Route::put('/kegiatan/{kegiatan}/pendaftaran-massal', [KegiatanController::class, 'ubahStatusPendaftaranMassal'])->name('kegiatan.pendaftaran.massal');

    // Instrumen generik K3.
    Route::get('/instrumen/{instrumen}/versi', [InstrumenController::class, 'versi'])->name('instrumen.versi');
    Route::post('/instrumen/{instrumen}/versi', [InstrumenController::class, 'simpanVersi'])->name('instrumen.versi.simpan');
    Route::get('/versi/{versi}/butir', [InstrumenController::class, 'butir'])->name('instrumen.butir');
    Route::post('/versi/{versi}/butir', [InstrumenController::class, 'simpanButir'])->name('instrumen.butir.simpan');
    Route::delete('/butir/{butir}', [InstrumenController::class, 'hapusButir'])->name('instrumen.butir.hapus');
    Route::post('/versi/{versi}/publikasi', [InstrumenController::class, 'publikasi'])->name('instrumen.publikasi');

    // Penilaian, poin, reward.
    Route::get('/penilaian', [AdminController::class, 'penilaian'])->name('penilaian');
    Route::post('/penilaian/{respons}/ulang', [AdminController::class, 'nilaiUlang'])->name('penilaian.ulang');
    Route::get('/transaksi-poin', [AdminController::class, 'transaksiPoin'])->name('poin');
    Route::post('/transaksi-poin', [AdminController::class, 'koreksiPoin'])->name('poin.koreksi');
    Route::get('/penukaran', [AdminController::class, 'penukaran'])->name('penukaran');
    Route::put('/penukaran/{penukaran}', [AdminController::class, 'ubahPenukaran'])->name('penukaran.ubah');

    // Sertifikat.
    Route::get('/sertifikat', [AdminController::class, 'sertifikat'])->name('sertifikat');
    Route::post('/sertifikat/massal', [AdminController::class, 'terbitMassal'])->name('sertifikat.massal');
    Route::post('/sertifikat/{sertifikat}/cabut', [AdminController::class, 'cabutSertifikat'])->name('sertifikat.cabut');

    // Laporan dan audit.
    Route::get('/laporan/{jenis}', [AdminController::class, 'laporan'])->name('laporan');
    Route::get('/laporan/{jenis}/ekspor', [AdminController::class, 'ekspor'])->name('laporan.ekspor');
    Route::get('/log-integrasi', [AdminController::class, 'log'])->name('log');
});
