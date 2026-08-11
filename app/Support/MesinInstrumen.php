<?php

namespace App\Support;

use App\Models\ButirInstrumen;
use App\Models\HasilPenilaian;
use App\Models\JawabanButir;
use App\Models\PelaksanaanInstrumen;
use App\Models\Pendaftaran;
use App\Models\ResponsInstrumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mesin generik K3: satu jalur pengerjaan dipakai ulang oleh demografi,
 * pre-test, post-test, dan kuesioner. Pembeda hanya kolom `fase`.
 */
class MesinInstrumen
{
    public const NILAI_LULUS = 70.0;

    public static function pelaksanaan(Pendaftaran $p, string $fase): ?PelaksanaanInstrumen
    {
        return PelaksanaanInstrumen::with('versi.instrumen')
            ->where('id_kegiatan', $p->id_kegiatan)
            ->where('fase', $fase)
            ->first();
    }

    /** Respons yang sedang dikerjakan; dibuat bila belum ada. Menolak bila sudah final. */
    public static function responsBerjalan(Pendaftaran $p, PelaksanaanInstrumen $pel): ResponsInstrumen
    {
        $final = ResponsInstrumen::where('id_pelaksanaan', $pel->id_pelaksanaan)
            ->where('id_pendaftaran', $p->id_pendaftaran)
            ->where('is_final', true)->first();

        if ($final) {
            throw ValidationException::withMessages([
                'instrumen' => 'Anda sudah mengirim respons final untuk tahap ini dan tidak dapat mengulanginya.',
            ]);
        }

        $berjalan = ResponsInstrumen::where('id_pelaksanaan', $pel->id_pelaksanaan)
            ->where('id_pendaftaran', $p->id_pendaftaran)
            ->where('status_respons', 'berlangsung')
            ->latest('percobaan_ke')->first();

        if ($berjalan) {
            return $berjalan;
        }

        $percobaan = 1 + (int) ResponsInstrumen::where('id_pelaksanaan', $pel->id_pelaksanaan)
            ->where('id_pendaftaran', $p->id_pendaftaran)->max('percobaan_ke');

        if ($percobaan > 3) {
            throw ValidationException::withMessages([
                'instrumen' => 'Batas maksimal tiga percobaan sudah tercapai. Hubungi panitia.',
            ]);
        }

        return ResponsInstrumen::create([
            'id_pelaksanaan' => $pel->id_pelaksanaan,
            'id_pendaftaran' => $p->id_pendaftaran,
            'percobaan_ke' => $percobaan,
        ]);
    }

    /**
     * Simpan jawaban lalu finalkan respons. Untuk fase tes, skor dihitung dan
     * disimpan di hasil_penilaian — terpisah dari poin gamifikasi.
     *
     * @param  array<int,mixed>  $jawaban  id_butir => id_opsi atau teks
     */
    public static function kirim(ResponsInstrumen $respons, array $jawaban): ResponsInstrumen
    {
        $butir = ButirInstrumen::with('opsi')
            ->where('id_versi', $respons->pelaksanaan->id_versi)->get();

        $pesan = [];
        foreach ($butir as $b) {
            $isi = $jawaban[$b->id_butir] ?? null;
            if ($b->wajib_diisi && ($isi === null || $isi === '')) {
                $pesan['jawaban.'.$b->id_butir] = 'Butir nomor '.$b->nomor_urut.' wajib diisi.';
            }
        }
        if ($pesan) {
            throw ValidationException::withMessages($pesan);
        }

        return DB::transaction(function () use ($respons, $butir, $jawaban) {
            foreach ($butir as $b) {
                $isi = $jawaban[$b->id_butir] ?? null;
                if ($isi === null || $isi === '') {
                    continue;
                }

                $pakaiOpsi = in_array($b->tipe_butir, ['pilihan_ganda', 'skala_likert'], true);

                JawabanButir::updateOrCreate(
                    ['id_respons' => $respons->id_respons, 'id_butir' => $b->id_butir],
                    $pakaiOpsi
                        ? ['id_opsi' => (int) $isi, 'teks_jawaban' => null]
                        : ['id_opsi' => null, 'teks_jawaban' => (string) $isi],
                );
            }

            $respons->update([
                'status_respons' => 'selesai',
                'is_final' => true,
                'selesai_pada' => now(),
            ]);

            if (in_array($respons->pelaksanaan->fase, ['pretest', 'posttest'], true)) {
                self::nilai($respons->fresh('jawaban'), $butir);
            }

            return $respons;
        });
    }

    /** Penilaian otomatis: bobot butir yang kuncinya terpilih, dinormalkan ke 0–100. */
    public static function nilai(ResponsInstrumen $respons, $butir = null): HasilPenilaian
    {
        $butir ??= ButirInstrumen::with('opsi')->where('id_versi', $respons->pelaksanaan->id_versi)->get();
        $jawaban = $respons->jawaban->keyBy('id_butir');

        $total = 0.0;
        $diperoleh = 0.0;

        foreach ($butir as $b) {
            if ($b->bobot_skor <= 0) {
                continue;
            }
            $total += $b->bobot_skor;
            $idOpsi = $jawaban[$b->id_butir]->id_opsi ?? null;
            if ($idOpsi && $b->opsi->firstWhere('id_opsi', $idOpsi)?->kunci_jawaban) {
                $diperoleh += $b->bobot_skor;
            }
        }

        $skor = $total > 0 ? round($diperoleh / $total * 100, 2) : 0.0;

        return HasilPenilaian::updateOrCreate(
            ['id_respons' => $respons->id_respons],
            [
                'skor' => $skor,
                'nilai_lulus' => self::NILAI_LULUS,
                'status_lulus' => $skor >= self::NILAI_LULUS,
                'dinilai_pada' => now(),
            ],
        );
    }
}
