<?php

namespace App\Http\Controllers;

use App\Models\AktivitasGamifikasi;
use App\Models\AktivitasPembelajaran;
use App\Models\ArtefakPeserta;
use App\Models\Badge;
use App\Models\Fasilitator;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\LogIntegrasi;
use App\Models\PartisipasiGamifikasi;
use App\Models\Pendaftaran;
use App\Models\Sesi;
use App\Models\TokenQrSesi;
use App\Models\TransaksiPoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FasilitatorController extends Controller
{
    public function dashboard()
    {
        $f = $this->fasilitator();
        $sesi = Sesi::with('kegiatan.sekolah.mitra')
            ->where('id_fasilitator', $f->id_fasilitator)
            ->orderBy('tanggal_sesi')->orderBy('jam_mulai')->get();

        return view('fasilitator.dashboard', [
            'f' => $f,
            'sesiHariIni' => $sesi->where('tanggal_sesi', '>=', today()->startOfDay())->take(10),
            'jumlahSesiHariIni' => $sesi->where('tanggal_sesi', today()->toDateString())->count(),
            'totalHadir' => Kehadiran::whereIn('id_sesi', $sesi->pluck('id_sesi'))->count(),
            'artefakMenunggu' => $this->kueriArtefak($f)->where('artefak_peserta.status_verifikasi', 'menunggu')->get(),
        ]);
    }

    public function kegiatan()
    {
        $f = $this->fasilitator();

        return view('fasilitator.kegiatan', [
            'kegiatan' => $f->kegiatan()->with('sekolah.mitra', 'program')->get(),
        ]);
    }

    public function kegiatanShow(Kegiatan $kegiatan)
    {
        $f = $this->fasilitator();
        $this->pastikanDitugaskan($kegiatan);

        return view('fasilitator.kegiatan-detail', [
            'k' => $kegiatan->load('sekolah.mitra', 'lokasi'),
            'sesi' => $kegiatan->sesi()->with('materi')->get(),
            'milikSaya' => $kegiatan->sesi()->where('id_fasilitator', $f->id_fasilitator)->pluck('id_sesi'),
        ]);
    }

    // -------------------------------------------------------------- QR & hadir

    public function qr(Sesi $sesi)
    {
        $this->pastikanSesiSaya($sesi);

        return view('fasilitator.qr', [
            's' => $sesi->load('kegiatan'),
            'token' => $sesi->tokenAktif(),
        ]);
    }

    public function buatToken(Request $request, Sesi $sesi)
    {
        $this->pastikanSesiSaya($sesi);
        $menit = max(1, min(120, $request->integer('menit', 15)));

        TokenQrSesi::create([
            'id_sesi' => $sesi->id_sesi,
            'dibuka_oleh' => $request->user()->id_pengguna,
            'token' => strtoupper(bin2hex(random_bytes(6))),
            'berlaku_hingga' => now()->addMinutes($menit),
        ]);
        LogIntegrasi::catat('kehadiran', 'buat_token_qr', 'sesi #'.$sesi->id_sesi);

        return back()->with('sukses', 'Token QR baru dibuat, berlaku '.$menit.' menit.');
    }

    public function kehadiran(Sesi $sesi)
    {
        $this->pastikanSesiSaya($sesi);

        return view('fasilitator.kehadiran', [
            's' => $sesi->load('kegiatan'),
            'pendaftaran' => Pendaftaran::with('peserta')
                ->where('id_kegiatan', $sesi->id_kegiatan)
                ->where('status_pendaftaran', '<>', 'dibatalkan')->get(),
            'hadir' => Kehadiran::where('id_sesi', $sesi->id_sesi)->get()->keyBy('id_pendaftaran'),
        ]);
    }

    public function hadirManual(Request $request, Sesi $sesi)
    {
        $this->pastikanSesiSaya($sesi);
        $id = $request->integer('id_pendaftaran');

        $pendaftaran = Pendaftaran::where('id_pendaftaran', $id)
            ->where('id_kegiatan', $sesi->id_kegiatan)->firstOrFail();

        Kehadiran::firstOrCreate(
            ['id_pendaftaran' => $pendaftaran->id_pendaftaran, 'id_sesi' => $sesi->id_sesi],
            ['metode_presensi' => 'manual'],
        );
        $pendaftaran->update(['status_pendaftaran' => 'hadir']);

        return back()->with('sukses', 'Kehadiran manual tercatat.');
    }

    // ----------------------------------------------------- aktivitas & artefak

    public function aktivitas(Sesi $sesi)
    {
        $this->pastikanSesiSaya($sesi);

        return view('fasilitator.aktivitas', [
            's' => $sesi,
            'aktivitas' => $sesi->aktivitas()->withCount('partisipasi')->get(),
            'gamifikasi' => $sesi->gamifikasi()->withCount('partisipasi')->get(),
            'pendaftaran' => Pendaftaran::with('peserta')
                ->where('id_kegiatan', $sesi->id_kegiatan)
                ->where('status_pendaftaran', '<>', 'dibatalkan')->get(),
        ]);
    }

    public function simpanAktivitas(Request $request, Sesi $sesi)
    {
        $this->pastikanSesiSaya($sesi);

        $data = $request->validate([
            'judul_aktivitas' => ['required', 'string', 'max:150'],
            'jenis_aktivitas' => ['required', 'in:materi_bacaan,diskusi,tugas_artefak'],
            'tool_ai' => ['nullable', 'in:canva,napkin,gamma,notebooklm,capcut,lainnya'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        AktivitasPembelajaran::create($data + ['id_sesi' => $sesi->id_sesi]);

        return back()->with('sukses', 'Aktivitas pembelajaran ditambahkan.');
    }

    public function simpanGamifikasi(Request $request, Sesi $sesi)
    {
        $this->pastikanSesiSaya($sesi);

        $data = $request->validate([
            'judul_gamifikasi' => ['required', 'string', 'max:150'],
            'jenis_gamifikasi' => ['required', 'in:kuis_praktik,game,tantangan'],
            'poin_maksimal' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        AktivitasGamifikasi::create($data + ['id_sesi' => $sesi->id_sesi]);

        return back()->with('sukses', 'Aktivitas gamifikasi ditambahkan.');
    }

    public function artefak()
    {
        return view('fasilitator.artefak', [
            'artefak' => $this->kueriArtefak($this->fasilitator())->get(),
        ]);
    }

    public function verifikasiArtefak(Request $request, ArtefakPeserta $artefak)
    {
        $data = $request->validate([
            'status_verifikasi' => ['required', 'in:terverifikasi,ditolak'],
            'catatan_revisi' => ['nullable', 'string', 'max:1000'],
        ]);

        $artefak->update($data);

        if ($data['status_verifikasi'] === 'terverifikasi') {
            $this->berikanBadge($artefak);
        }
        LogIntegrasi::catat('aktivitas', 'verifikasi_artefak', 'artefak #'.$artefak->id_artefak);

        return back()->with('sukses', 'Status artefak diperbarui.');
    }

    // ------------------------------------------------------- poin & rekap nilai

    public function nilaiGamifikasi(Request $request, AktivitasGamifikasi $gamifikasi)
    {
        $this->pastikanSesiSaya($gamifikasi->sesi);

        $data = $request->validate([
            'id_pendaftaran' => ['required', 'integer'],
            'poin' => ['required', 'integer', 'min:-1000', 'max:1000', 'not_in:0'],
            'keterangan' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($gamifikasi, $data) {
            $partisipasi = PartisipasiGamifikasi::firstOrCreate(
                ['id_gamifikasi' => $gamifikasi->id_gamifikasi, 'id_pendaftaran' => $data['id_pendaftaran']],
                ['skor_permainan' => 0],
            );
            $partisipasi->increment('skor_permainan', max(0, $data['poin']));

            TransaksiPoin::create([
                'id_pendaftaran' => $data['id_pendaftaran'],
                'id_partisipasi_gamifikasi' => $partisipasi->id_partisipasi_g,
                'jenis_transaksi' => 'koreksi',
                'jumlah_poin' => $data['poin'],
                'keterangan' => $data['keterangan'],
            ]);
        });

        return back()->with('sukses', 'Poin dicatat sebagai koreksi bertanggung jawab.');
    }

    public function rekap()
    {
        $f = $this->fasilitator();
        $idKegiatan = $f->kegiatan()->pluck('kegiatan.id_kegiatan');

        return view('fasilitator.rekap', [
            'baris' => DB::table('pendaftaran as pd')
                ->join('peserta as ps', 'ps.id_peserta', '=', 'pd.id_peserta')
                ->join('kegiatan as k', 'k.id_kegiatan', '=', 'pd.id_kegiatan')
                ->leftJoin('v_hasil_belajar as hb', 'hb.id_pendaftaran', '=', 'pd.id_pendaftaran')
                ->whereIn('pd.id_kegiatan', $idKegiatan)
                ->select('ps.nama_peserta', 'k.tema', 'pd.status_pendaftaran',
                    'hb.skor_pretest', 'hb.skor_posttest', 'hb.selisih_skor')
                ->orderBy('k.tema')->orderBy('ps.nama_peserta')->get(),
        ]);
    }

    // ---------------------------------------------------------------- private

    private function fasilitator(): Fasilitator
    {
        $f = Fasilitator::where('id_pengguna', auth()->id())->first();
        abort_unless($f, 403, 'Akun Anda belum ditautkan ke data fasilitator. Hubungi admin.');

        return $f;
    }

    private function pastikanDitugaskan(Kegiatan $k): void
    {
        abort_unless(
            $this->fasilitator()->kegiatan()->where('kegiatan.id_kegiatan', $k->id_kegiatan)->exists(),
            403, 'Anda tidak ditugaskan pada kegiatan ini.',
        );
    }

    private function pastikanSesiSaya(Sesi $sesi): void
    {
        abort_unless($sesi->id_fasilitator === $this->fasilitator()->id_fasilitator, 403,
            'Sesi ini dibawakan fasilitator lain.');
    }

    private function kueriArtefak(Fasilitator $f)
    {
        return ArtefakPeserta::query()
            ->join('partisipasi_aktivitas as pa', 'pa.id_partisipasi', '=', 'artefak_peserta.id_partisipasi')
            ->join('aktivitas_pembelajaran as ap', 'ap.id_aktivitas', '=', 'pa.id_aktivitas')
            ->join('sesi as s', 's.id_sesi', '=', 'ap.id_sesi')
            ->join('pendaftaran as pd', 'pd.id_pendaftaran', '=', 'pa.id_pendaftaran')
            ->join('peserta as ps', 'ps.id_peserta', '=', 'pd.id_peserta')
            ->where('s.id_fasilitator', $f->id_fasilitator)
            ->orderByDesc('artefak_peserta.id_artefak')
            ->select('artefak_peserta.*', 'ps.nama_peserta', 'ap.judul_aktivitas', 'ap.tool_ai', 's.judul_sesi');
    }

    private function berikanBadge(ArtefakPeserta $artefak): void
    {
        $badge = Badge::where('nama_badge', 'Kreator Digital')->first();
        $idPendaftaran = $artefak->partisipasi->id_pendaftaran;

        if ($badge && ! DB::table('badge_peserta')
            ->where('id_badge', $badge->id_badge)->where('id_pendaftaran', $idPendaftaran)->exists()) {
            DB::table('badge_peserta')->insert([
                'id_badge' => $badge->id_badge,
                'id_pendaftaran' => $idPendaftaran,
                'diperoleh_pada' => now(),
            ]);
        }
    }
}
