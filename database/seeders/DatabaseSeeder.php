<?php

namespace Database\Seeders;

use App\Models\AktivitasGamifikasi;
use App\Models\AktivitasPembelajaran;
use App\Models\Afiliasi;
use App\Models\Badge;
use App\Models\ButirInstrumen;
use App\Models\Fasilitator;
use App\Models\HasilPenilaian;
use App\Models\Instrumen;
use App\Models\JawabanButir;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\KonfigurasiEvaluasiKegiatan;
use App\Models\IndikatorEvaluasi;
use App\Models\Lokasi;
use App\Models\Materi;
use App\Models\Mitra;
use App\Models\OpsiButir;
use App\Models\PartisipasiGamifikasi;
use App\Models\PelaksanaanInstrumen;
use App\Models\Pendaftaran;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\PersetujuanPeserta;
use App\Models\Peserta;
use App\Models\ProgramPkm;
use App\Models\ResponsInstrumen;
use App\Models\Reward;
use App\Models\Sekolah;
use App\Models\Sertifikat;
use App\Models\Sesi;
use App\Models\TransaksiPoin;
use App\Models\VersiInstrumen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const SANDI = 'password123';

    public function run(): void
    {
        $peran = $this->peran();
        [$adminAkun, $fasilitatorAkun, $pesertaAkun] = $this->pengguna($peran);
        [$sekolah, $lokasi, $mitraKomunitas] = $this->mitraDanSekolah();
        $program = $this->program($sekolah);
        $fasilitator = $this->fasilitator($fasilitatorAkun);
        $materi = $this->materi();
        $kegiatan = $this->kegiatan($program, $sekolah, $lokasi);
        $sesi = $this->sesi($kegiatan, $fasilitator, $materi);
        $this->aktivitas($sesi);
        $this->badgeDanReward();
        $instrumen = $this->instrumen();
        $this->pelaksanaan($kegiatan, $instrumen);
        $this->pesertaDanAlur($pesertaAkun, $kegiatan, $sesi, $instrumen, $sekolah, $mitraKomunitas);

        $this->command?->info('Seed selesai. Akun uji memakai kata sandi: '.self::SANDI);
    }

    // ----------------------------------------------------------------- K1

    private function peran(): array
    {
        $daftar = [
            'admin' => 'Administrator / Panitia',
            'penyelenggara' => 'Penyelenggara Program',
            'fasilitator' => 'Fasilitator',
            'peserta' => 'Peserta',
        ];

        $out = [];
        foreach ($daftar as $kode => $nama) {
            $out[$kode] = Peran::firstOrCreate(['kode_peran' => $kode], ['nama_peran' => $nama]);
        }

        return $out;
    }

    private function pengguna(array $peran): array
    {
        $buat = function (string $nama, string $email, string $kode) use ($peran) {
            $p = Pengguna::firstOrCreate(
                ['email' => $email],
                ['nama_pengguna' => $nama, 'kata_sandi_hash' => Hash::make(self::SANDI)],
            );
            $p->peran()->syncWithoutDetaching([$peran[$kode]->id_peran]);

            return $p;
        };

        $admin = $buat('Panitia CyberAware', 'admin@cyberaware.test', 'admin');

        $fasilitator = [
            $buat('Rizky Ananda', 'fasilitator1@cyberaware.test', 'fasilitator'),
            $buat('Sari Dewi Lestari', 'fasilitator2@cyberaware.test', 'fasilitator'),
        ];

        $peserta = [
            $buat('Nadia Putri Ramadhani', 'peserta1@cyberaware.test', 'peserta'),
            $buat('Bagas Aditya', 'peserta2@cyberaware.test', 'peserta'),
            $buat('Fajar Nugroho', 'peserta3@cyberaware.test', 'peserta'),
        ];

        return [$admin, $fasilitator, $peserta];
    }

    private function mitraDanSekolah(): array
    {
        $daftar = [
            ['SMA Negeri 3 Kota Malang', '20536112', 'SMA', 'Malang', ['Aula Utama' => 120, 'Lab Komputer 1' => 36]],
            ['SMK Telkom Bandung', '20224457', 'SMK', 'Bandung', ['Auditorium' => 200]],
            ['SMP Islam Al-Azhar', '20101233', 'SMP', 'Jakarta Selatan', ['Ruang Multimedia' => 60]],
        ];

        $sekolah = [];
        $lokasi = [];

        foreach ($daftar as [$nama, $npsn, $jenjang, $kota, $ruang]) {
            $mitra = Mitra::firstOrCreate(['nama_mitra' => $nama], [
                'jenis_mitra' => 'sekolah',
                'kontak_email' => 'humas@'.str($nama)->slug().'.sch.id',
                'alamat' => $kota,
            ]);

            $s = Sekolah::firstOrCreate(['id_mitra' => $mitra->id_mitra],
                ['npsn' => $npsn, 'jenjang' => $jenjang, 'kota' => $kota]);
            $sekolah[] = $s;

            foreach ($ruang as $namaRuang => $kapasitas) {
                $lokasi[] = Lokasi::firstOrCreate(
                    ['id_sekolah' => $s->id_sekolah, 'nama_lokasi' => $namaRuang],
                    ['kapasitas_ruang' => $kapasitas],
                );
            }
        }

        $komunitas = Mitra::firstOrCreate(['nama_mitra' => 'Komunitas Relawan TIK'],
            ['jenis_mitra' => 'komunitas', 'alamat' => 'Jakarta']);

        return [$sekolah, $lokasi, $komunitas];
    }

    private function program(array $sekolah): array
    {
        $daftar = [
            ['Cerdas Berinternet', 'Program literasi digital dasar bagi siswa menengah.', '2026-07-01', null, 'berjalan'],
            ['Sekolah Anti Phishing', 'Pelatihan pengenalan dan penanganan phishing di lingkungan sekolah.', '2026-08-01', null, 'berjalan'],
            ['Password Sehat', 'Kampanye kebiasaan kata sandi kuat dan manajemen kredensial.', '2026-03-01', '2026-05-30', 'selesai'],
        ];

        $out = [];
        foreach ($daftar as [$nama, $desk, $mulai, $selesai, $status]) {
            $p = ProgramPkm::firstOrCreate(['nama_program' => $nama], [
                'deskripsi' => $desk,
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'status_program' => $status,
            ]);
            $p->mitra()->syncWithoutDetaching(
                collect($sekolah)->pluck('id_mitra')->mapWithKeys(fn ($id) => [$id => ['peran_mitra' => 'tuan rumah']])->all()
            );
            $out[] = $p;
        }

        return $out;
    }

    private function fasilitator(array $akun): array
    {
        $bidang = ['Keamanan Jaringan & Phishing', 'Privasi Digital & Media Sosial'];

        $out = [];
        foreach ($akun as $i => $p) {
            $out[] = Fasilitator::firstOrCreate(['id_pengguna' => $p->id_pengguna], [
                'nama_fasilitator' => $p->nama_pengguna,
                'email' => $p->email,
                'bidang_keahlian' => $bidang[$i],
            ]);
        }

        return $out;
    }

    // ----------------------------------------------------------------- K4

    private function materi(): array
    {
        $daftar = [
            ['Mengenali Email Phishing', 'phishing', 'Ciri-ciri email penipuan dan cara memverifikasinya.'],
            ['Kata Sandi Kuat & Pengelola Sandi', 'kata_sandi', 'Panjang, keunikan, dan penggunaan password manager.'],
            ['Jejak Digital & Privasi', 'privasi_digital', 'Data pribadi apa saja yang tersebar dan cara membatasinya.'],
            ['Etika Bermedia Sosial', 'etika_medsos', 'Berinteraksi sehat dan menghindari doxing.'],
        ];

        $out = [];
        foreach ($daftar as [$judul, $kategori, $desk]) {
            $out[] = Materi::firstOrCreate(['judul_materi' => $judul],
                ['kategori' => $kategori, 'deskripsi' => $desk]);
        }

        return $out;
    }

    private function kegiatan(array $program, array $sekolah, array $lokasi): array
    {
        $daftar = [
            [$program[1], $sekolah[0], $lokasi[0], 'Kenali Phishing Sejak Dini', '2026-08-18', 60, 'luring', 'berlangsung'],
            [$program[0], $sekolah[1], $lokasi[2], 'Amankan Akun Media Sosialmu', '2026-08-25', 80, 'hybrid', 'terjadwal'],
            [$program[0], $sekolah[2], null, 'Privasi Digital untuk Pelajar', '2026-09-02', 50, 'daring', 'terjadwal'],
            [$program[2], $sekolah[0], $lokasi[1], 'Menjaga Data Pribadi di Ruang Publik', '2026-10-10', 40, 'luring', 'terjadwal'],
            [$program[2], $sekolah[1], $lokasi[2], 'Workshop Pembuatan Password Super', '2026-10-15', 100, 'daring', 'terjadwal'],
        ];

        $out = [];
        foreach ($daftar as [$p, $s, $l, $tema, $tanggal, $kapasitas, $mode, $status]) {
            $out[] = Kegiatan::firstOrCreate(['tema' => $tema], [
                'id_program' => $p->id_program,
                'id_sekolah' => $s->id_sekolah,
                'id_lokasi' => $l?->id_lokasi,
                'tanggal_mulai' => $tanggal,
                'kapasitas' => $kapasitas,
                'mode_pelaksanaan' => $mode,
                'status_kegiatan' => $status,
            ]);
        }

        return $out;
    }

    private function sesi(array $kegiatan, array $fasilitator, array $materi): array
    {
        $rencana = [
            [0, 1, 'Pengenalan Phishing', '08:00', '09:30', 0, [0]],
            [0, 2, 'Simulasi & Diskusi Kasus', '09:45', '11:15', 1, [0, 3]],
            [1, 1, 'Kata Sandi & Autentikasi Ganda', '08:00', '09:30', 1, [1]],
            [2, 1, 'Jejak Digital Pelajar', '13:00', '14:30', 0, [2]],
            [3, 1, 'Pentingnya Menjaga Data', '08:00', '10:00', 0, [2]],
            [4, 1, 'Praktek Membuat Password', '10:00', '12:00', 1, [1]],
        ];

        $out = [];
        foreach ($rencana as [$ik, $urut, $judul, $mulai, $selesai, $ifas, $imat]) {
            $k = $kegiatan[$ik];
            $s = Sesi::firstOrCreate(
                ['id_kegiatan' => $k->id_kegiatan, 'urutan_sesi' => $urut],
                [
                    'id_fasilitator' => $fasilitator[$ifas]->id_fasilitator,
                    'judul_sesi' => $judul,
                    'tanggal_sesi' => $k->tanggal_mulai,
                    'jam_mulai' => $mulai,
                    'jam_selesai' => $selesai,
                ],
            );
            $s->materi()->syncWithoutDetaching(collect($imat)->map(fn ($i) => $materi[$i]->id_materi)->all());
            $k->fasilitator()->syncWithoutDetaching([
                $fasilitator[$ifas]->id_fasilitator => ['peran_penugasan' => 'pemateri'],
            ]);
            $out[] = $s;
        }

        return $out;
    }

    private function aktivitas(array $sesi): void
    {
        AktivitasPembelajaran::firstOrCreate(
            ['id_sesi' => $sesi[0]->id_sesi, 'judul_aktivitas' => 'Bacaan: Anatomi Email Phishing'],
            ['jenis_aktivitas' => 'materi_bacaan', 'deskripsi' => 'Baca studi kasus email phishing bank.'],
        );
        AktivitasPembelajaran::firstOrCreate(
            ['id_sesi' => $sesi[1]->id_sesi, 'judul_aktivitas' => 'Poster Waspada Phishing'],
            [
                'jenis_aktivitas' => 'tugas_artefak',
                'tool_ai' => 'canva',
                'deskripsi' => 'Buat poster edukasi menggunakan tool AI, lalu unggah tautannya.',
            ],
        );
        AktivitasPembelajaran::firstOrCreate(
            ['id_sesi' => $sesi[2]->id_sesi, 'judul_aktivitas' => 'Infografis Kata Sandi Kuat'],
            ['jenis_aktivitas' => 'tugas_artefak', 'tool_ai' => 'napkin', 'deskripsi' => 'Rangkum aturan kata sandi kuat dalam satu infografis.'],
        );

        foreach ([
            [$sesi[0], 'Kuis Kenali Email Palsu', 'kuis_praktik', 100],
            [$sesi[1], 'Tantangan Kata Sandi Kuat', 'tantangan', 150],
            [$sesi[1], 'Game Deteksi Tautan Berbahaya', 'game', 200],
            [$sesi[2], 'Kuis Sandi', 'kuis_praktik', 100],
            [$sesi[3], 'Diskusi Jejak Digital', 'tantangan', 100],
            [$sesi[4], 'Game Data Publik', 'game', 100],
            [$sesi[5], 'Simulasi Password', 'kuis_praktik', 150],
        ] as [$s, $judul, $jenis, $poin]) {
            AktivitasGamifikasi::firstOrCreate(
                ['id_sesi' => $s->id_sesi, 'judul_gamifikasi' => $judul],
                ['jenis_gamifikasi' => $jenis, 'poin_maksimal' => $poin],
            );
        }
    }

    private function badgeDanReward(): void
    {
        foreach ([
            ['Pemburu Phishing', 'Menyelesaikan kuis pengenalan phishing.', 'Skor kuis praktik phishing di atas 80%.'],
            ['Kata Sandi Tangguh', 'Menuntaskan tantangan kata sandi kuat.', 'Menyelesaikan tantangan kata sandi.'],
            ['Peserta Aktif', 'Hadir pada seluruh sesi kegiatan.', 'Kehadiran penuh pada satu kegiatan.'],
            ['Kreator Digital', 'Artefak tool AI terverifikasi fasilitator.', 'Minimal satu artefak berstatus terverifikasi.'],
            ['Peningkatan Terbaik', 'Selisih post-test terhadap pre-test tertinggi.', 'Selisih skor tertinggi pada satu kegiatan.'],
            ['Penyumbang Evaluasi', 'Mengisi kuesioner penyelenggaraan.', 'Respons kuesioner final terkirim.'],
        ] as [$nama, $desk, $kriteria]) {
            Badge::firstOrCreate(['nama_badge' => $nama], ['deskripsi' => $desk, 'kriteria' => $kriteria]);
        }

        foreach ([
            ['Tumbler CyberAware', 200, 14],
            ['Totebag Edisi Terbatas', 300, 0],
            ['Voucher Buku Rp50rb', 400, 6],
        ] as [$nama, $biaya, $stok]) {
            Reward::firstOrCreate(['nama_reward' => $nama], ['biaya_poin' => $biaya, 'stok' => $stok]);
        }
    }

    // ----------------------------------------------------------------- K3/K5

    private function instrumen(): array
    {
        $demografi = $this->buatInstrumen('DEMO-01', 'Data Demografi Peserta', 'demografi', [
            ['Jenis kelamin', 'pilihan_ganda', 0, [['Laki-laki', 0, false], ['Perempuan', 0, false]]],
            ['Kelas / tingkat', 'isian_singkat', 0, []],
            ['Seberapa sering Anda memakai media sosial?', 'skala_likert', 0, [
                ['Tidak pernah', 1, false], ['Kadang', 2, false], ['Sering', 3, false], ['Sangat sering', 4, false],
            ]],
        ]);

        $tes = $this->buatInstrumen('TES-CYB-01', 'Pengetahuan Dasar Keamanan Siber', 'tes', [
            ['Manakah tanda paling umum dari email phishing?', 'pilihan_ganda', 20, [
                ['Pengirim memaksa klik tautan dalam waktu terbatas', 1, true],
                ['Email berasal dari domain resmi sekolah', 0, false],
                ['Terdapat lampiran PDF dari guru', 0, false],
            ]],
            ['Kata sandi berikut yang paling kuat adalah…', 'pilihan_ganda', 20, [
                ['nama-tanggal lahir', 0, false],
                ['frasa panjang unik antar-akun', 1, true],
                ['satu kata sandi untuk semua akun', 0, false],
            ]],
            ['Apa langkah pertama bila akun media sosial diretas?', 'pilihan_ganda', 20, [
                ['Membuat akun baru', 0, false],
                ['Mengganti kata sandi dan mengaktifkan verifikasi dua langkah', 1, true],
                ['Membiarkannya sampai pulih sendiri', 0, false],
            ]],
            ['Informasi mana yang TIDAK layak dibagikan publik?', 'pilihan_ganda', 20, [
                ['Nomor induk siswa dan alamat rumah', 1, true],
                ['Nama sekolah', 0, false],
                ['Hobi', 0, false],
            ]],
            ['Verifikasi dua langkah berfungsi untuk…', 'pilihan_ganda', 20, [
                ['Mempercepat login', 0, false],
                ['Menambah lapisan keamanan selain kata sandi', 1, true],
                ['Menghapus riwayat penelusuran', 0, false],
            ]],
        ]);

        $kuesioner = $this->buatInstrumen('EVAL-01', 'Evaluasi Penyelenggaraan Kegiatan', 'kuesioner', [
            ['Materi yang disampaikan relevan dengan kebutuhan saya.', 'skala_likert', 0, $this->likert(), 'materi'],
            ['Fasilitator menyampaikan materi dengan jelas.', 'skala_likert', 0, $this->likert(), 'fasilitator'],
            ['Metode pembelajaran membuat saya terlibat aktif.', 'skala_likert', 0, $this->likert(), 'metode'],
            ['Fasilitas dan platform yang digunakan memadai.', 'skala_likert', 0, $this->likert(), 'fasilitas_platform'],
            ['Kegiatan ini bermanfaat bagi keseharian digital saya.', 'skala_likert', 0, $this->likert(), 'manfaat'],
            ['Secara keseluruhan saya puas dengan kegiatan ini.', 'skala_likert', 0, $this->likert(), 'kepuasan'],
            ['Saran perbaikan untuk kegiatan berikutnya.', 'esai', 0, [], 'saran'],
        ]);

        return compact('demografi', 'tes', 'kuesioner');
    }

    /** @return array<int,array{0:string,1:int,2:bool}> */
    private function likert(): array
    {
        return [
            ['Sangat tidak setuju', 1, false],
            ['Tidak setuju', 2, false],
            ['Netral', 3, false],
            ['Setuju', 4, false],
            ['Sangat setuju', 5, false],
        ];
    }

    private function buatInstrumen(string $kode, string $nama, string $tipe, array $butir): VersiInstrumen
    {
        $instrumen = Instrumen::firstOrCreate(['kode_instrumen' => $kode],
            ['nama_instrumen' => $nama, 'tipe_instrumen' => $tipe]);

        $versi = VersiInstrumen::firstOrCreate(
            ['id_instrumen' => $instrumen->id_instrumen, 'nomor_versi' => 1],
            ['status_versi' => 'terkunci', 'dikunci_pada' => now()],
        );

        if ($versi->butir()->exists()) {
            return $versi;
        }

        foreach ($butir as $i => $b) {
            [$teks, $tipeButir, $bobot, $opsi] = $b;

            $baris = ButirInstrumen::create([
                'id_versi' => $versi->id_versi,
                'nomor_urut' => $i + 1,
                'teks_butir' => $teks,
                'tipe_butir' => $tipeButir,
                'bobot_skor' => $bobot,
                'wajib_diisi' => true,
            ]);

            foreach ($opsi as $j => [$teksOpsi, $nilai, $kunci]) {
                OpsiButir::create([
                    'id_butir' => $baris->id_butir,
                    'teks_opsi' => $teksOpsi,
                    'nilai_skor' => $nilai,
                    'kunci_jawaban' => $kunci,
                    'urutan_opsi' => $j + 1,
                ]);
            }

            if (isset($b[4])) {
                IndikatorEvaluasi::firstOrCreate(['id_butir' => $baris->id_butir], ['aspek_dinilai' => $b[4]]);
            }
        }

        return $versi;
    }

    private function pelaksanaan(array $kegiatan, array $instrumen): void
    {
        foreach ($kegiatan as $k) {
            foreach (['demografi' => $instrumen['demografi'], 'pretest' => $instrumen['tes'], 'posttest' => $instrumen['tes']] as $fase => $versi) {
                PelaksanaanInstrumen::firstOrCreate(
                    ['id_kegiatan' => $k->id_kegiatan, 'fase' => $fase],
                    ['id_versi' => $versi->id_versi, 'dibuka_pada' => now()->subDay()],
                );
            }

            KonfigurasiEvaluasiKegiatan::firstOrCreate(['id_kegiatan' => $k->id_kegiatan], [
                'id_versi' => $instrumen['kuesioner']->id_versi,
                'mode_evaluasi' => 'identitas',
                'dibuka_pada' => now()->subDay(),
            ]);

            PelaksanaanInstrumen::firstOrCreate(
                ['id_kegiatan' => $k->id_kegiatan, 'fase' => 'kuesioner'],
                ['id_versi' => $instrumen['kuesioner']->id_versi, 'dibuka_pada' => now()->subDay()],
            );
        }
    }

    // ----------------------------------------------------------------- K2

    private function pesertaDanAlur(array $akun, array $kegiatan, array $sesi, array $instrumen, array $sekolah, Mitra $komunitas): void
    {
        $kegiatanUtama = $kegiatan[0];

        foreach ($akun as $i => $pengguna) {
            $peserta = Peserta::firstOrCreate(['email' => $pengguna->email],
                ['nama_peserta' => $pengguna->nama_pengguna, 'no_hp' => '08120000000'.$i]);

            $pendaftaran = Pendaftaran::firstOrCreate([
                'id_peserta' => $peserta->id_peserta,
                'id_kegiatan' => $kegiatanUtama->id_kegiatan,
            ]);

            Afiliasi::firstOrCreate(['id_pendaftaran' => $pendaftaran->id_pendaftaran], [
                'id_mitra' => $i === 2 ? $komunitas->id_mitra : $sekolah[0]->id_mitra,
                'peran_afiliasi' => $i === 2 ? 'umum' : 'siswa',
            ]);

            PersetujuanPeserta::firstOrCreate(['id_pendaftaran' => $pendaftaran->id_pendaftaran], [
                'versi_kebijakan' => '1.0',
                'disetujui' => true,
                'waktu_persetujuan' => now()->subDays(2),
            ]);

            // Peserta pertama dibuat sampai tuntas supaya seluruh dashboard dan
            // view laporan berisi data nyata sejak seed pertama.
            if ($i > 0) {
                continue;
            }

            Kehadiran::firstOrCreate(
                ['id_pendaftaran' => $pendaftaran->id_pendaftaran, 'id_sesi' => $sesi[0]->id_sesi],
                ['metode_presensi' => 'manual'],
            );
            $pendaftaran->update(['status_pendaftaran' => 'hadir']);

            $this->responsTes($pendaftaran, $kegiatanUtama, 'pretest', 2);
            $this->responsTes($pendaftaran, $kegiatanUtama, 'posttest', 5);
            $this->responsKuesioner($pendaftaran, $kegiatanUtama, $instrumen['kuesioner']);

            $gamifikasi = AktivitasGamifikasi::where('id_sesi', $sesi[0]->id_sesi)->first();
            if ($gamifikasi && ! PartisipasiGamifikasi::where('id_gamifikasi', $gamifikasi->id_gamifikasi)
                ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)->exists()) {
                $partisipasi = PartisipasiGamifikasi::create([
                    'id_gamifikasi' => $gamifikasi->id_gamifikasi,
                    'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                    'skor_permainan' => 90,
                    'waktu_selesai' => now()->subDay(),
                ]);
                TransaksiPoin::create([
                    'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                    'id_partisipasi_gamifikasi' => $partisipasi->id_partisipasi_g,
                    'jenis_transaksi' => 'perolehan',
                    'jumlah_poin' => 90,
                    'keterangan' => $gamifikasi->judul_gamifikasi,
                ]);
            }

            // Sertifikat tidak lagi di-generate otomatis oleh seeder agar alur penerbitan manual oleh admin dapat dites.
        }
    }

    /** Membuat respons tes final beserta jawaban dan hasil penilaian. */
    private function responsTes(Pendaftaran $pendaftaran, Kegiatan $kegiatan, string $fase, int $jumlahBenar): void
    {
        $pelaksanaan = PelaksanaanInstrumen::where('id_kegiatan', $kegiatan->id_kegiatan)->where('fase', $fase)->first();
        if (! $pelaksanaan || ResponsInstrumen::where('id_pelaksanaan', $pelaksanaan->id_pelaksanaan)
            ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)->exists()) {
            return;
        }

        $respons = ResponsInstrumen::create([
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'id_pendaftaran' => $pendaftaran->id_pendaftaran,
            'status_respons' => 'selesai',
            'is_final' => true,
            'selesai_pada' => now()->subDay(),
        ]);

        $butir = ButirInstrumen::with('opsi')->where('id_versi', $pelaksanaan->id_versi)->get();
        $benar = 0;

        foreach ($butir as $b) {
            $kunci = $b->opsi->firstWhere('kunci_jawaban', true);
            $pilih = $benar < $jumlahBenar ? $kunci : $b->opsi->firstWhere('kunci_jawaban', false);
            if ($pilih === $kunci) {
                $benar++;
            }

            JawabanButir::create([
                'id_respons' => $respons->id_respons,
                'id_butir' => $b->id_butir,
                'id_opsi' => $pilih?->id_opsi,
                'teks_jawaban' => $pilih ? null : '-',
            ]);
        }

        $skor = round($benar / max(1, $butir->count()) * 100, 2);

        HasilPenilaian::create([
            'id_respons' => $respons->id_respons,
            'skor' => $skor,
            'nilai_lulus' => 70,
            'status_lulus' => $skor >= 70,
        ]);
    }

    private function responsKuesioner(Pendaftaran $pendaftaran, Kegiatan $kegiatan, VersiInstrumen $versi): void
    {
        $pelaksanaan = PelaksanaanInstrumen::where('id_kegiatan', $kegiatan->id_kegiatan)->where('fase', 'kuesioner')->first();
        if (! $pelaksanaan || ResponsInstrumen::where('id_pelaksanaan', $pelaksanaan->id_pelaksanaan)
            ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)->exists()) {
            return;
        }

        $respons = ResponsInstrumen::create([
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'id_pendaftaran' => $pendaftaran->id_pendaftaran,
            'status_respons' => 'selesai',
            'is_final' => true,
            'selesai_pada' => now()->subHours(6),
        ]);

        foreach (ButirInstrumen::with('opsi')->where('id_versi', $versi->id_versi)->get() as $b) {
            $opsi = $b->opsi->firstWhere('nilai_skor', 4) ?? $b->opsi->last();

            JawabanButir::create([
                'id_respons' => $respons->id_respons,
                'id_butir' => $b->id_butir,
                'id_opsi' => $opsi?->id_opsi,
                'teks_jawaban' => $opsi ? null : 'Perbanyak sesi praktik langsung.',
            ]);
        }
    }
}
