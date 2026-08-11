<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilPenilaian;
use App\Models\Kegiatan;
use App\Models\LogIntegrasi;
use App\Models\Pendaftaran;
use App\Models\PenukaranReward;
use App\Models\ResponsInstrumen;
use App\Models\Reward;
use App\Models\Sertifikat;
use App\Models\TransaksiPoin;
use App\Support\Alur;
use App\Support\MesinInstrumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'totalPeserta' => DB::table('peserta')->count(),
            'kegiatanAktif' => Kegiatan::whereIn('status_kegiatan', ['terjadwal', 'berlangsung'])->count(),
            'sertifikatTerbit' => Sertifikat::where('status_sertifikat', 'terbit')->count(),
            'poinBeredar' => (int) TransaksiPoin::sum('jumlah_poin'),
            'rekap' => DB::table('v_rekap_kegiatan')->orderByDesc('jumlah_pendaftar')->limit(8)->get(),
            'pendaftaranTerbaru' => Pendaftaran::with('peserta', 'kegiatan')
                ->orderByDesc('id_pendaftaran')->limit(8)->get(),
        ]);
    }

    // ------------------------------------------------------- penilaian & poin

    public function penilaian(Request $request)
    {
        $q = DB::table('hasil_penilaian as hp')
            ->join('respons_instrumen as r', 'r.id_respons', '=', 'hp.id_respons')
            ->join('pelaksanaan_instrumen as pi', 'pi.id_pelaksanaan', '=', 'r.id_pelaksanaan')
            ->join('pendaftaran as pd', 'pd.id_pendaftaran', '=', 'r.id_pendaftaran')
            ->join('peserta as ps', 'ps.id_peserta', '=', 'pd.id_peserta')
            ->join('kegiatan as k', 'k.id_kegiatan', '=', 'pi.id_kegiatan')
            ->select('hp.id_penilaian', 'hp.id_respons', 'ps.nama_peserta', 'k.tema', 'pi.fase',
                'hp.skor', 'hp.status_lulus', 'r.is_final');

        if ($request->filled('kegiatan')) {
            $q->where('k.id_kegiatan', $request->integer('kegiatan'));
        }

        return view('admin.penilaian', [
            'baris' => $q->orderByDesc('hp.id_penilaian')->paginate(25)->withQueryString(),
            'kegiatan' => Kegiatan::orderBy('tema')->get(),
        ]);
    }

    public function nilaiUlang(ResponsInstrumen $respons)
    {
        MesinInstrumen::nilai($respons->load('jawaban', 'pelaksanaan'));
        LogIntegrasi::catat('penilaian', 'nilai_ulang', 'respons #'.$respons->id_respons);

        return back()->with('sukses', 'Respons dinilai ulang.');
    }

    public function transaksiPoin(Request $request)
    {
        return view('admin.transaksi-poin', [
            'baris' => TransaksiPoin::with('pendaftaran.peserta', 'pendaftaran.kegiatan')
                ->orderByDesc('id_transaksi')->paginate(25),
            'pendaftaran' => Pendaftaran::with('peserta', 'kegiatan')->orderByDesc('id_pendaftaran')->limit(200)->get(),
        ]);
    }

    public function koreksiPoin(Request $request)
    {
        $data = $request->validate([
            'id_pendaftaran' => ['required', 'integer', 'exists:pendaftaran,id_pendaftaran'],
            'jumlah_poin' => ['required', 'integer', 'not_in:0', 'min:-10000', 'max:10000'],
            'keterangan' => ['required', 'string', 'max:255'],
        ]);

        TransaksiPoin::create($data + ['jenis_transaksi' => 'koreksi']);
        LogIntegrasi::catat('poin', 'koreksi_manual', $data['keterangan']);

        return back()->with('sukses', 'Koreksi poin tercatat.');
    }

    public function penukaran()
    {
        return view('admin.penukaran', [
            'baris' => PenukaranReward::with('reward', 'pendaftaran.peserta')
                ->orderByDesc('id_penukaran')->paginate(25),
        ]);
    }

    public function ubahPenukaran(Request $request, PenukaranReward $penukaran)
    {
        $data = $request->validate([
            'status_penukaran' => ['required', 'in:diproses,selesai,dibatalkan'],
        ]);

        DB::transaction(function () use ($penukaran, $data) {
            // Pembatalan mengembalikan stok dan poin agar saldo tetap sesuai histori.
            if ($data['status_penukaran'] === 'dibatalkan' && $penukaran->status_penukaran !== 'dibatalkan') {
                Reward::where('id_reward', $penukaran->id_reward)->increment('stok');
                TransaksiPoin::create([
                    'id_pendaftaran' => $penukaran->id_pendaftaran,
                    'id_penukaran' => $penukaran->id_penukaran,
                    'jenis_transaksi' => 'koreksi',
                    'jumlah_poin' => $penukaran->biaya_poin_saat_itu,
                    'keterangan' => 'Pengembalian poin: penukaran dibatalkan',
                ]);
            }
            $penukaran->update($data);
        });

        return back()->with('sukses', 'Status penukaran diperbarui.');
    }

    // ------------------------------------------------------------- sertifikat

    public function sertifikat(Request $request)
    {
        return view('admin.sertifikat', [
            'baris' => Sertifikat::with('pendaftaran.peserta', 'pendaftaran.kegiatan')
                ->orderByDesc('id_sertifikat')->paginate(25),
            'kegiatan' => Kegiatan::orderBy('tema')->get(),
        ]);
    }

    public function terbitMassal(Request $request)
    {
        $request->validate(['id_kegiatan' => ['required', 'integer', 'exists:kegiatan,id_kegiatan']]);

        $terbit = 0;
        foreach (Pendaftaran::where('id_kegiatan', $request->integer('id_kegiatan'))->get() as $p) {
            if ($p->sertifikat || ! Alur::layakSertifikat($p)) {
                continue;
            }
            Sertifikat::create([
                'id_pendaftaran' => $p->id_pendaftaran,
                'nomor_sertifikat' => sprintf('CAQ/%s/%05d', now()->year, $p->id_pendaftaran),
                'kode_verifikasi' => 'VF-'.strtoupper(bin2hex(random_bytes(3))),
            ]);
            $terbit++;
        }
        LogIntegrasi::catat('sertifikat', 'terbit_massal', $terbit.' sertifikat');

        return back()->with('sukses', $terbit.' sertifikat diterbitkan. Peserta yang belum memenuhi syarat dilewati.');
    }

    public function cabutSertifikat(Sertifikat $sertifikat)
    {
        $sertifikat->update(['status_sertifikat' => 'dicabut']);
        LogIntegrasi::catat('sertifikat', 'cabut', $sertifikat->nomor_sertifikat);

        return back()->with('sukses', 'Sertifikat dicabut.');
    }

    // ---------------------------------------------------------------- laporan

    public function laporan(Request $request, string $jenis)
    {
        $def = $this->definisiLaporan($jenis);
        $idKegiatan = $request->integer('kegiatan') ?: null;

        return view('admin.laporan', [
            'jenis' => $jenis,
            'def' => $def,
            'baris' => ($def['kueri'])($idKegiatan)->limit(500)->get(),
            'kegiatan' => Kegiatan::orderBy('tema')->get(),
            'terpilih' => $idKegiatan,
            'daftar' => $this->daftarLaporan(),
        ]);
    }

    public function ekspor(Request $request, string $jenis): StreamedResponse
    {
        $def = $this->definisiLaporan($jenis);
        $baris = ($def['kueri'])($request->integer('kegiatan') ?: null)->get();
        $nama = 'laporan-'.$jenis.'-'.now()->format('Ymd-His').'.csv';

        LogIntegrasi::catat('laporan', 'ekspor_csv', $jenis);

        return response()->streamDownload(function () use ($def, $baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            fputcsv($out, array_values($def['kolom']));
            foreach ($baris as $b) {
                fputcsv($out, array_map(fn ($k) => $b->{$k} ?? '', array_keys($def['kolom'])));
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function log()
    {
        return view('admin.log', [
            'baris' => LogIntegrasi::with('pengguna')->orderByDesc('id_log')->paginate(50),
        ]);
    }

    /** @return array<string,string> */
    public function daftarLaporan(): array
    {
        return [
            'hasil-belajar' => 'Hasil Belajar (pre vs post)',
            'evaluasi' => 'Evaluasi Penyelenggaraan',
            'rekap-kegiatan' => 'Rekap Kegiatan',
            'leaderboard' => 'Leaderboard Poin',
            'saldo-poin' => 'Saldo Poin',
            'administrasi' => 'Administrasi Program & Kegiatan',
            'kehadiran' => 'Rekap Kehadiran per Sesi',
            'artefak' => 'Artefak & Badge',
        ];
    }

    private function definisiLaporan(string $jenis): array
    {
        $semua = [
            'hasil-belajar' => [
                'judul' => 'Hasil Belajar (v_hasil_belajar)',
                'kolom' => ['nama_peserta' => 'Peserta', 'tema' => 'Kegiatan', 'skor_pretest' => 'Pre-test',
                    'skor_posttest' => 'Post-test', 'selisih_skor' => 'Selisih'],
                'kueri' => fn (?int $k) => DB::table('v_hasil_belajar as hb')
                    ->join('pendaftaran as pd', 'pd.id_pendaftaran', '=', 'hb.id_pendaftaran')
                    ->join('peserta as ps', 'ps.id_peserta', '=', 'pd.id_peserta')
                    ->join('kegiatan as kg', 'kg.id_kegiatan', '=', 'pd.id_kegiatan')
                    ->when($k, fn ($q) => $q->where('pd.id_kegiatan', $k))
                    ->select('ps.nama_peserta', 'kg.tema', 'hb.skor_pretest', 'hb.skor_posttest', 'hb.selisih_skor')
                    ->orderByDesc('hb.selisih_skor'),
            ],
            'evaluasi' => [
                'judul' => 'Evaluasi Penyelenggaraan (v_evaluasi_kegiatan)',
                'kolom' => ['tema' => 'Kegiatan', 'aspek_dinilai' => 'Aspek', 'rata_rata_skala' => 'Rata-rata', 'jumlah_respons' => 'Respons'],
                'kueri' => fn (?int $k) => DB::table('v_evaluasi_kegiatan as ev')
                    ->join('kegiatan as kg', 'kg.id_kegiatan', '=', 'ev.id_kegiatan')
                    ->when($k, fn ($q) => $q->where('ev.id_kegiatan', $k))
                    ->select('kg.tema', 'ev.aspek_dinilai', 'ev.rata_rata_skala', 'ev.jumlah_respons')
                    ->orderBy('kg.tema')->orderBy('ev.aspek_dinilai'),
            ],
            'rekap-kegiatan' => [
                'judul' => 'Rekap Kegiatan (v_rekap_kegiatan)',
                'kolom' => ['tema' => 'Kegiatan', 'jumlah_pendaftar' => 'Pendaftar', 'jumlah_hadir' => 'Hadir', 'persen_hadir' => '% Hadir'],
                'kueri' => fn (?int $k) => DB::table('v_rekap_kegiatan as rk')
                    ->when($k, fn ($q) => $q->where('rk.id_kegiatan', $k))
                    ->selectRaw("rk.tema, rk.jumlah_pendaftar, rk.jumlah_hadir,
                        CASE WHEN rk.jumlah_pendaftar = 0 THEN 0
                             ELSE ROUND(rk.jumlah_hadir * 100.0 / rk.jumlah_pendaftar, 1) END AS persen_hadir")
                    ->orderByDesc('rk.jumlah_pendaftar'),
            ],
            'leaderboard' => [
                'judul' => 'Leaderboard Poin (v_leaderboard)',
                'kolom' => ['peringkat' => 'Peringkat', 'nama_peserta' => 'Peserta', 'tema' => 'Kegiatan', 'total_poin_diperoleh' => 'Poin diperoleh'],
                'kueri' => fn (?int $k) => DB::table('v_leaderboard as l')
                    ->join('pendaftaran as pd', 'pd.id_pendaftaran', '=', 'l.id_pendaftaran')
                    ->join('kegiatan as kg', 'kg.id_kegiatan', '=', 'pd.id_kegiatan')
                    ->when($k, fn ($q) => $q->where('pd.id_kegiatan', $k))
                    ->select('l.peringkat', 'l.nama_peserta', 'kg.tema', 'l.total_poin_diperoleh')
                    ->orderByDesc('l.total_poin_diperoleh'),
            ],
            'saldo-poin' => [
                'judul' => 'Saldo Poin (v_saldo_poin)',
                'kolom' => ['nama_peserta' => 'Peserta', 'tema' => 'Kegiatan', 'saldo_poin' => 'Saldo'],
                'kueri' => fn (?int $k) => DB::table('v_saldo_poin as sp')
                    ->join('pendaftaran as pd', 'pd.id_pendaftaran', '=', 'sp.id_pendaftaran')
                    ->join('peserta as ps', 'ps.id_peserta', '=', 'pd.id_peserta')
                    ->join('kegiatan as kg', 'kg.id_kegiatan', '=', 'pd.id_kegiatan')
                    ->when($k, fn ($q) => $q->where('pd.id_kegiatan', $k))
                    ->select('ps.nama_peserta', 'kg.tema', 'sp.saldo_poin')
                    ->orderByDesc('sp.saldo_poin'),
            ],
            'administrasi' => [
                'judul' => 'Administrasi Program & Kegiatan',
                'kolom' => ['nama_program' => 'Program', 'tema' => 'Kegiatan', 'sekolah' => 'Sekolah', 'lokasi' => 'Lokasi',
                    'tanggal_mulai' => 'Mulai', 'mode_pelaksanaan' => 'Mode', 'kapasitas' => 'Kapasitas',
                    'status_kegiatan' => 'Status', 'jumlah_fasilitator' => 'Fasilitator', 'jumlah_pendaftar' => 'Pendaftar'],
                'kueri' => fn (?int $k) => DB::table('kegiatan as kg')
                    ->join('program_pkm as pr', 'pr.id_program', '=', 'kg.id_program')
                    ->join('sekolah as sk', 'sk.id_sekolah', '=', 'kg.id_sekolah')
                    ->join('mitra as mt', 'mt.id_mitra', '=', 'sk.id_mitra')
                    ->leftJoin('lokasi as lk', 'lk.id_lokasi', '=', 'kg.id_lokasi')
                    ->when($k, fn ($q) => $q->where('kg.id_kegiatan', $k))
                    ->selectRaw("pr.nama_program, kg.tema, mt.nama_mitra AS sekolah,
                        COALESCE(lk.nama_lokasi, '-') AS lokasi, kg.tanggal_mulai, kg.mode_pelaksanaan,
                        kg.kapasitas, kg.status_kegiatan,
                        (SELECT COUNT(*) FROM penugasan_fasilitator pf WHERE pf.id_kegiatan = kg.id_kegiatan) AS jumlah_fasilitator,
                        (SELECT COUNT(*) FROM pendaftaran p WHERE p.id_kegiatan = kg.id_kegiatan) AS jumlah_pendaftar")
                    ->orderBy('pr.nama_program')->orderBy('kg.tanggal_mulai'),
            ],
            'kehadiran' => [
                'judul' => 'Rekap Kehadiran per Sesi',
                'kolom' => ['tema' => 'Kegiatan', 'judul_sesi' => 'Sesi', 'tanggal_sesi' => 'Tanggal',
                    'jumlah_pendaftar' => 'Pendaftar', 'jumlah_hadir' => 'Hadir', 'persen_hadir' => '% Hadir'],
                'kueri' => fn (?int $k) => DB::table('sesi as s')
                    ->join('kegiatan as kg', 'kg.id_kegiatan', '=', 's.id_kegiatan')
                    ->when($k, fn ($q) => $q->where('s.id_kegiatan', $k))
                    ->selectRaw("kg.tema, s.judul_sesi, s.tanggal_sesi,
                        (SELECT COUNT(*) FROM pendaftaran p WHERE p.id_kegiatan = s.id_kegiatan) AS jumlah_pendaftar,
                        (SELECT COUNT(*) FROM kehadiran h WHERE h.id_sesi = s.id_sesi) AS jumlah_hadir,
                        CASE WHEN (SELECT COUNT(*) FROM pendaftaran p WHERE p.id_kegiatan = s.id_kegiatan) = 0 THEN 0
                             ELSE ROUND((SELECT COUNT(*) FROM kehadiran h WHERE h.id_sesi = s.id_sesi) * 100.0 /
                                        (SELECT COUNT(*) FROM pendaftaran p WHERE p.id_kegiatan = s.id_kegiatan), 1) END AS persen_hadir")
                    ->orderBy('kg.tema')->orderBy('s.urutan_sesi'),
            ],
            'artefak' => [
                'judul' => 'Artefak per Tool AI & Badge',
                'kolom' => ['nama_peserta' => 'Peserta', 'tema' => 'Kegiatan', 'judul_aktivitas' => 'Aktivitas',
                    'tool_ai' => 'Tool AI', 'judul_artefak' => 'Artefak', 'status_verifikasi' => 'Verifikasi',
                    'diunggah_pada' => 'Diunggah', 'jumlah_badge' => 'Badge'],
                'kueri' => fn (?int $k) => DB::table('artefak_peserta as ar')
                    ->join('partisipasi_aktivitas as pa', 'pa.id_partisipasi', '=', 'ar.id_partisipasi')
                    ->join('aktivitas_pembelajaran as ap', 'ap.id_aktivitas', '=', 'pa.id_aktivitas')
                    ->join('pendaftaran as pd', 'pd.id_pendaftaran', '=', 'pa.id_pendaftaran')
                    ->join('peserta as ps', 'ps.id_peserta', '=', 'pd.id_peserta')
                    ->join('kegiatan as kg', 'kg.id_kegiatan', '=', 'pd.id_kegiatan')
                    ->when($k, fn ($q) => $q->where('pd.id_kegiatan', $k))
                    ->selectRaw("ps.nama_peserta, kg.tema, ap.judul_aktivitas, COALESCE(ap.tool_ai,'-') AS tool_ai,
                        ar.judul_artefak, ar.status_verifikasi, ar.diunggah_pada,
                        (SELECT COUNT(*) FROM badge_peserta bp WHERE bp.id_pendaftaran = pd.id_pendaftaran) AS jumlah_badge")
                    ->orderByDesc('ar.id_artefak'),
            ],
        ];

        abort_unless(isset($semua[$jenis]), 404);

        return $semua[$jenis];
    }
}
