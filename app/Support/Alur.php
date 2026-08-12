<?php

namespace App\Support;

use App\Models\Kehadiran;
use App\Models\Pendaftaran;
use App\Models\PelaksanaanInstrumen;

/**
 * Gerbang enam tahap alur peserta.
 *
 * Semua status diturunkan dari kolom yang sudah ada (persetujuan, is_final,
 * kehadiran) — tidak ada kolom status tahapan baru, supaya tidak melahirkan
 * atribut turunan yang melanggar 3NF.
 */
class Alur
{
    /** @return array<int,array{kunci:string,label:string,status:string,alasan:?string}> */
    public static function tahapan(Pendaftaran $p): array
    {
        $setuju = (bool) $p->persetujuan?->disetujui;
        $demografi = $setuju && $p->responsFinal('demografi') !== null;
        $pretest = $demografi && $p->responsFinal('pretest') !== null;
        $hadir = $pretest && Kehadiran::where('id_pendaftaran', $p->id_pendaftaran)->exists();
        $posttest = $hadir && $p->responsFinal('posttest') !== null;
        $kuesioner = $posttest && $p->responsFinal('kuesioner') !== null;

        // Admin bisa menunda tampilnya hasil pretest/posttest (kolom
        // tampilkan_hasil, default true) sebelum peserta boleh lanjut ke
        // tahap berikutnya.
        $flagTampil = PelaksanaanInstrumen::where('id_kegiatan', $p->id_kegiatan)
            ->pluck('tampilkan_hasil', 'fase');
        $pretestTampil = (bool) ($flagTampil['pretest'] ?? true);
        $posttestTampil = (bool) ($flagTampil['posttest'] ?? true);

        $urut = [
            ['persetujuan', 'Persetujuan & Demografi', true, $demografi, null],
            ['pretest', 'Pre-test', $setuju && $demografi, $pretest, 'Selesaikan persetujuan dan demografi lebih dulu.'],
            ['kehadiran', 'Check-in & Materi', $pretest && $pretestTampil, $hadir, 'Tunggu admin menampilkan hasil pre-test Anda.'],
            ['posttest', 'Post-test', $hadir, $posttest, 'Diperlukan minimal satu kehadiran sesi.'],
            ['kuesioner', 'Kuesioner', $posttest && $posttestTampil, $kuesioner, 'Tunggu admin menampilkan hasil post-test Anda.'],
            ['sertifikat', 'Sertifikat', $kuesioner, $p->sertifikat !== null, 'Kuesioner penyelenggaraan belum dikirim.'],
        ];

        return array_map(fn ($t) => [
            'kunci' => $t[0],
            'label' => $t[1],
            'status' => $t[3] ? 'selesai' : ($t[2] ? 'aktif' : 'terkunci'),
            'alasan' => $t[2] ? null : $t[4],
        ], $urut);
    }

    /** Cek satu gerbang; kembalikan pesan alasan bila belum boleh dibuka. */
    public static function alasanTerkunci(Pendaftaran $p, string $kunci): ?string
    {
        foreach (self::tahapan($p) as $t) {
            if ($t['kunci'] === $kunci) {
                return $t['status'] === 'terkunci' ? $t['alasan'] : null;
            }
        }

        return null;
    }

    /** Syarat penerbitan sertifikat: hadir, pre-test, post-test, dan kuesioner terkirim. */
    public static function layakSertifikat(Pendaftaran $p): bool
    {
        return Kehadiran::where('id_pendaftaran', $p->id_pendaftaran)->exists()
            && $p->responsFinal('pretest')
            && $p->responsFinal('posttest')
            && $p->responsFinal('kuesioner');
    }
}
