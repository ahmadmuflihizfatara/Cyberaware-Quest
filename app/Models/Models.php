<?php

/**
 * Seluruh model Eloquent proyek ini dikumpulkan dalam satu berkas.
 *
 * ponytail: 38 tabel = 38 berkas model yang isinya rata-rata 6 baris. Menaruhnya
 * dalam satu berkas membuat seluruh peta relasi terbaca sekali duduk. Pecah per
 * berkas kalau nanti ada model yang tumbuh punya logika sendiri.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** Basis semua model: tabel memakai kolom waktu sendiri, bukan created_at/updated_at. */
abstract class Base extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}

// =============================================================================
// KELOMPOK 1 — PROGRAM, KEGIATAN, DAN PENGGUNA
// =============================================================================

class Pengguna extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $guarded = [];

    protected $hidden = ['kata_sandi_hash'];

    /** Tidak ada kolom remember_token pada skema; fitur "ingat saya" dimatikan. */
    protected $rememberTokenName = '';

    public function getAuthPassword(): string
    {
        return $this->kata_sandi_hash;
    }

    public function peran(): BelongsToMany
    {
        return $this->belongsToMany(Peran::class, 'pengguna_peran', 'id_pengguna', 'id_peran');
    }

    public function fasilitator(): HasOne
    {
        return $this->hasOne(Fasilitator::class, 'id_pengguna');
    }

    /** Peserta ditautkan lewat email karena skema K2 sengaja tidak menyimpan id_pengguna. */
    public function peserta(): HasOne
    {
        return $this->hasOne(Peserta::class, 'email', 'email');
    }

    public function punyaPeran(string ...$kode): bool
    {
        return $this->peran->pluck('kode_peran')->intersect($kode)->isNotEmpty();
    }

    public function peranUtama(): string
    {
        foreach (['admin', 'penyelenggara', 'fasilitator', 'peserta'] as $kode) {
            if ($this->peran->contains('kode_peran', $kode)) {
                return $kode;
            }
        }

        return 'peserta';
    }
}

class Peran extends Base
{
    protected $table = 'peran';

    protected $primaryKey = 'id_peran';
}

class Mitra extends Base
{
    protected $table = 'mitra';

    protected $primaryKey = 'id_mitra';

    public function sekolah(): HasOne
    {
        return $this->hasOne(Sekolah::class, 'id_mitra');
    }
}

class Sekolah extends Base
{
    protected $table = 'sekolah';

    protected $primaryKey = 'id_sekolah';

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'id_mitra');
    }

    public function lokasi(): HasMany
    {
        return $this->hasMany(Lokasi::class, 'id_sekolah');
    }

    public function getNamaAttribute(): string
    {
        return $this->mitra?->nama_mitra ?? ('Sekolah #'.$this->id_sekolah);
    }
}

class Lokasi extends Base
{
    protected $table = 'lokasi';

    protected $primaryKey = 'id_lokasi';

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'id_sekolah');
    }
}

class ProgramPkm extends Base
{
    protected $table = 'program_pkm';

    protected $primaryKey = 'id_program';

    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class, 'id_program');
    }

    public function mitra(): BelongsToMany
    {
        return $this->belongsToMany(Mitra::class, 'program_mitra', 'id_program', 'id_mitra')
            ->withPivot('peran_mitra');
    }
}

class Fasilitator extends Base
{
    protected $table = 'fasilitator';

    protected $primaryKey = 'id_fasilitator';

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    public function sesi(): HasMany
    {
        return $this->hasMany(Sesi::class, 'id_fasilitator');
    }

    public function kegiatan(): BelongsToMany
    {
        return $this->belongsToMany(Kegiatan::class, 'penugasan_fasilitator', 'id_fasilitator', 'id_kegiatan')
            ->withPivot('peran_penugasan');
    }
}

class Kegiatan extends Base
{
    protected $table = 'kegiatan';

    protected $primaryKey = 'id_kegiatan';

    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramPkm::class, 'id_program');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'id_sekolah');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi');
    }

    public function sesi(): HasMany
    {
        return $this->hasMany(Sesi::class, 'id_kegiatan')->orderBy('urutan_sesi');
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class, 'id_kegiatan');
    }

    public function fasilitator(): BelongsToMany
    {
        return $this->belongsToMany(Fasilitator::class, 'penugasan_fasilitator', 'id_kegiatan', 'id_fasilitator')
            ->withPivot('peran_penugasan');
    }

    public function pelaksanaan(): HasMany
    {
        return $this->hasMany(PelaksanaanInstrumen::class, 'id_kegiatan');
    }

    public function konfigurasiEvaluasi(): HasOne
    {
        return $this->hasOne(KonfigurasiEvaluasiKegiatan::class, 'id_kegiatan');
    }

    public function sisaKuota(): int
    {
        return max(0, $this->kapasitas - $this->pendaftaran()
            ->where('status_pendaftaran', '<>', 'dibatalkan')->count());
    }
}

// =============================================================================
// KELOMPOK 4 — SESI, MATERI, AKTIVITAS, GAMIFIKASI
// =============================================================================

class Materi extends Base
{
    protected $table = 'materi';

    protected $primaryKey = 'id_materi';
}

class Sesi extends Base
{
    protected $table = 'sesi';

    protected $primaryKey = 'id_sesi';

    protected $casts = ['tanggal_sesi' => 'date'];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan');
    }

    public function fasilitator(): BelongsTo
    {
        return $this->belongsTo(Fasilitator::class, 'id_fasilitator');
    }

    public function materi(): BelongsToMany
    {
        return $this->belongsToMany(Materi::class, 'sesi_materi', 'id_sesi', 'id_materi');
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(AktivitasPembelajaran::class, 'id_sesi');
    }

    public function gamifikasi(): HasMany
    {
        return $this->hasMany(AktivitasGamifikasi::class, 'id_sesi');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class, 'id_sesi');
    }

    public function tokenAktif(): ?TokenQrSesi
    {
        return TokenQrSesi::where('id_sesi', $this->id_sesi)
            ->where('berlaku_hingga', '>', now())
            ->latest('id_token')->first();
    }
}

class AktivitasPembelajaran extends Base
{
    protected $table = 'aktivitas_pembelajaran';

    protected $primaryKey = 'id_aktivitas';

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(Sesi::class, 'id_sesi');
    }

    public function partisipasi(): HasMany
    {
        return $this->hasMany(PartisipasiAktivitas::class, 'id_aktivitas');
    }
}

class PartisipasiAktivitas extends Base
{
    protected $table = 'partisipasi_aktivitas';

    protected $primaryKey = 'id_partisipasi';

    protected $casts = ['waktu_selesai' => 'datetime'];

    public function aktivitas(): BelongsTo
    {
        return $this->belongsTo(AktivitasPembelajaran::class, 'id_aktivitas');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'id_pendaftaran');
    }

    public function artefak(): HasOne
    {
        return $this->hasOne(ArtefakPeserta::class, 'id_partisipasi');
    }
}

class ArtefakPeserta extends Base
{
    protected $table = 'artefak_peserta';

    protected $primaryKey = 'id_artefak';

    protected $casts = ['diunggah_pada' => 'datetime'];

    public function partisipasi(): BelongsTo
    {
        return $this->belongsTo(PartisipasiAktivitas::class, 'id_partisipasi');
    }
}

class AktivitasGamifikasi extends Base
{
    protected $table = 'aktivitas_gamifikasi';

    protected $primaryKey = 'id_gamifikasi';

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(Sesi::class, 'id_sesi');
    }

    public function partisipasi(): HasMany
    {
        return $this->hasMany(PartisipasiGamifikasi::class, 'id_gamifikasi');
    }
}

class PartisipasiGamifikasi extends Base
{
    protected $table = 'partisipasi_gamifikasi';

    protected $primaryKey = 'id_partisipasi_g';

    protected $casts = ['waktu_selesai' => 'datetime'];

    public function gamifikasi(): BelongsTo
    {
        return $this->belongsTo(AktivitasGamifikasi::class, 'id_gamifikasi');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'id_pendaftaran');
    }
}

class Badge extends Base
{
    protected $table = 'badge';

    protected $primaryKey = 'id_badge';

    public function pendaftaran(): BelongsToMany
    {
        return $this->belongsToMany(Pendaftaran::class, 'badge_peserta', 'id_badge', 'id_pendaftaran')
            ->withPivot('diperoleh_pada');
    }
}

class Reward extends Base
{
    protected $table = 'reward';

    protected $primaryKey = 'id_reward';

    protected $casts = ['status_aktif' => 'boolean'];
}

class PenukaranReward extends Base
{
    protected $table = 'penukaran_reward';

    protected $primaryKey = 'id_penukaran';

    protected $casts = ['waktu_penukaran' => 'datetime'];

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class, 'id_reward');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'id_pendaftaran');
    }
}

class TransaksiPoin extends Base
{
    protected $table = 'transaksi_poin';

    protected $primaryKey = 'id_transaksi';

    protected $casts = ['dibuat_pada' => 'datetime'];

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'id_pendaftaran');
    }
}

// =============================================================================
// KELOMPOK 2 — PESERTA, PENDAFTARAN, KEHADIRAN
// =============================================================================

class Peserta extends Base
{
    protected $table = 'peserta';

    protected $primaryKey = 'id_peserta';

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class, 'id_peserta');
    }
}

class Pendaftaran extends Base
{
    protected $table = 'pendaftaran';

    protected $primaryKey = 'id_pendaftaran';

    protected $casts = ['tanggal_daftar' => 'datetime'];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'id_peserta');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan');
    }

    public function afiliasi(): HasOne
    {
        return $this->hasOne(Afiliasi::class, 'id_pendaftaran');
    }

    public function persetujuan(): HasOne
    {
        return $this->hasOne(PersetujuanPeserta::class, 'id_pendaftaran');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class, 'id_pendaftaran');
    }

    public function respons(): HasMany
    {
        return $this->hasMany(ResponsInstrumen::class, 'id_pendaftaran');
    }

    public function transaksiPoin(): HasMany
    {
        return $this->hasMany(TransaksiPoin::class, 'id_pendaftaran');
    }

    public function badge(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'badge_peserta', 'id_pendaftaran', 'id_badge')
            ->withPivot('diperoleh_pada');
    }

    public function sertifikat(): HasOne
    {
        return $this->hasOne(Sertifikat::class, 'id_pendaftaran');
    }

    public function saldoPoin(): int
    {
        return (int) $this->transaksiPoin()->sum('jumlah_poin');
    }

    /** Respons final untuk sebuah fase pelaksanaan instrumen, bila sudah ada. */
    public function responsFinal(string $fase): ?ResponsInstrumen
    {
        return ResponsInstrumen::where('id_pendaftaran', $this->id_pendaftaran)
            ->where('is_final', true)
            ->whereIn('id_pelaksanaan', PelaksanaanInstrumen::where('id_kegiatan', $this->id_kegiatan)
                ->where('fase', $fase)->pluck('id_pelaksanaan'))
            ->first();
    }
}

class Afiliasi extends Base
{
    protected $table = 'afiliasi';

    protected $primaryKey = 'id_afiliasi';

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'id_mitra');
    }
}

class PersetujuanPeserta extends Base
{
    protected $table = 'persetujuan_peserta';

    protected $primaryKey = 'id_persetujuan';

    protected $casts = ['disetujui' => 'boolean', 'waktu_persetujuan' => 'datetime'];
}

class TokenQrSesi extends Base
{
    protected $table = 'token_qr_sesi';

    protected $primaryKey = 'id_token';

    protected $casts = ['berlaku_hingga' => 'datetime'];

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(Sesi::class, 'id_sesi');
    }

    public function masihBerlaku(): bool
    {
        return $this->berlaku_hingga->isFuture();
    }
}

class Kehadiran extends Base
{
    protected $table = 'kehadiran';

    protected $primaryKey = 'id_kehadiran';

    protected $casts = ['waktu_hadir' => 'datetime'];

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(Sesi::class, 'id_sesi');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'id_pendaftaran');
    }
}

// =============================================================================
// KELOMPOK 3 — INSTRUMEN, RESPONS, PENILAIAN
// =============================================================================

class Instrumen extends Base
{
    protected $table = 'instrumen';

    protected $primaryKey = 'id_instrumen';

    public function versi(): HasMany
    {
        return $this->hasMany(VersiInstrumen::class, 'id_instrumen')->orderBy('nomor_versi');
    }
}

class VersiInstrumen extends Base
{
    protected $table = 'versi_instrumen';

    protected $primaryKey = 'id_versi';

    protected $casts = ['dikunci_pada' => 'datetime'];

    public function instrumen(): BelongsTo
    {
        return $this->belongsTo(Instrumen::class, 'id_instrumen');
    }

    public function butir(): HasMany
    {
        return $this->hasMany(ButirInstrumen::class, 'id_versi')->orderBy('nomor_urut');
    }

    public function terkunci(): bool
    {
        return $this->status_versi !== 'draft';
    }
}

class ButirInstrumen extends Base
{
    protected $table = 'butir_instrumen';

    protected $primaryKey = 'id_butir';

    protected $casts = ['wajib_diisi' => 'boolean', 'bobot_skor' => 'float'];

    public function versi(): BelongsTo
    {
        return $this->belongsTo(VersiInstrumen::class, 'id_versi');
    }

    public function opsi(): HasMany
    {
        return $this->hasMany(OpsiButir::class, 'id_butir')->orderBy('urutan_opsi');
    }

    public function indikator(): HasOne
    {
        return $this->hasOne(IndikatorEvaluasi::class, 'id_butir');
    }
}

class OpsiButir extends Base
{
    protected $table = 'opsi_butir';

    protected $primaryKey = 'id_opsi';

    protected $casts = ['kunci_jawaban' => 'boolean', 'nilai_skor' => 'float'];
}

class PelaksanaanInstrumen extends Base
{
    protected $table = 'pelaksanaan_instrumen';

    protected $primaryKey = 'id_pelaksanaan';

    protected $casts = ['dibuka_pada' => 'datetime', 'ditutup_pada' => 'datetime'];

    public function versi(): BelongsTo
    {
        return $this->belongsTo(VersiInstrumen::class, 'id_versi');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan');
    }

    public function terbuka(): bool
    {
        return (! $this->dibuka_pada || $this->dibuka_pada->isPast())
            && (! $this->ditutup_pada || $this->ditutup_pada->isFuture());
    }
}

class ResponsInstrumen extends Base
{
    protected $table = 'respons_instrumen';

    protected $primaryKey = 'id_respons';

    protected $casts = ['is_final' => 'boolean', 'mulai_pada' => 'datetime', 'selesai_pada' => 'datetime'];

    public function pelaksanaan(): BelongsTo
    {
        return $this->belongsTo(PelaksanaanInstrumen::class, 'id_pelaksanaan');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'id_pendaftaran');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(JawabanButir::class, 'id_respons');
    }

    public function penilaian(): HasOne
    {
        return $this->hasOne(HasilPenilaian::class, 'id_respons');
    }
}

class JawabanButir extends Base
{
    protected $table = 'jawaban_butir';

    protected $primaryKey = 'id_jawaban';

    public function opsi(): BelongsTo
    {
        return $this->belongsTo(OpsiButir::class, 'id_opsi');
    }

    public function butir(): BelongsTo
    {
        return $this->belongsTo(ButirInstrumen::class, 'id_butir');
    }
}

class HasilPenilaian extends Base
{
    protected $table = 'hasil_penilaian';

    protected $primaryKey = 'id_penilaian';

    protected $casts = ['skor' => 'float', 'nilai_lulus' => 'float', 'status_lulus' => 'boolean', 'dinilai_pada' => 'datetime'];

    public function respons(): BelongsTo
    {
        return $this->belongsTo(ResponsInstrumen::class, 'id_respons');
    }
}

// =============================================================================
// KELOMPOK 5 — EVALUASI, SERTIFIKAT, LOG
// =============================================================================

class KonfigurasiEvaluasiKegiatan extends Base
{
    protected $table = 'konfigurasi_evaluasi_kegiatan';

    protected $primaryKey = 'id_konfigurasi';

    protected $casts = ['dibuka_pada' => 'datetime', 'ditutup_pada' => 'datetime'];

    public function versi(): BelongsTo
    {
        return $this->belongsTo(VersiInstrumen::class, 'id_versi');
    }
}

class IndikatorEvaluasi extends Base
{
    protected $table = 'indikator_evaluasi';

    protected $primaryKey = 'id_indikator';
}

class Sertifikat extends Base
{
    protected $table = 'sertifikat';

    protected $primaryKey = 'id_sertifikat';

    protected $casts = ['diterbitkan_pada' => 'datetime'];

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'id_pendaftaran');
    }
}

class LogIntegrasi extends Base
{
    protected $table = 'log_integrasi';

    protected $primaryKey = 'id_log';

    protected $casts = ['dibuat_pada' => 'datetime'];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh');
    }

    public static function catat(string $modul, string $kejadian, ?string $keterangan = null): void
    {
        static::create([
            'nama_modul' => $modul,
            'jenis_kejadian' => $kejadian,
            'keterangan' => $keterangan,
            'dibuat_oleh' => auth()->id(),
        ]);
    }
}
