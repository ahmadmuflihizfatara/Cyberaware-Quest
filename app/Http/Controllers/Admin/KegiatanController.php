<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fasilitator;
use App\Models\IndikatorEvaluasi;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\KonfigurasiEvaluasiKegiatan;
use App\Models\Lokasi;
use App\Models\Materi;
use App\Models\PelaksanaanInstrumen;
use App\Models\ProgramPkm;
use App\Models\Sekolah;
use App\Models\Sesi;
use App\Models\VersiInstrumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KegiatanController extends Controller
{
    public function index()
    {
        return view('admin.kegiatan.index', [
            'kegiatan' => Kegiatan::with('program', 'sekolah.mitra', 'lokasi')
                ->withCount('pendaftaran')->orderByDesc('id_kegiatan')->paginate(20),
            'program' => ProgramPkm::orderBy('nama_program')->get(),
            'sekolah' => Sekolah::with('mitra')->get(),
            'lokasi' => Lokasi::with('sekolah.mitra')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Kegiatan::create($this->validasi($request));

        return back()->with('sukses', 'Kegiatan dibuat.');
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $kegiatan->update($this->validasi($request));

        return back()->with('sukses', 'Kegiatan diperbarui.');
    }

    public function show(Kegiatan $kegiatan)
    {
        $kegiatan->load('program', 'sekolah.mitra', 'lokasi', 'sesi.fasilitator', 'sesi.materi',
            'fasilitator', 'pelaksanaan.versi.instrumen', 'konfigurasiEvaluasi.versi');

        $progres = DB::table('pendaftaran as pd')
            ->join('peserta as ps', 'ps.id_peserta', '=', 'pd.id_peserta')
            ->leftJoin('afiliasi as a', 'a.id_pendaftaran', '=', 'pd.id_pendaftaran')
            ->leftJoin('mitra as m', 'm.id_mitra', '=', 'a.id_mitra')
            ->leftJoin('v_hasil_belajar as hb', 'hb.id_pendaftaran', '=', 'pd.id_pendaftaran')
            ->where('pd.id_kegiatan', $kegiatan->id_kegiatan)
            ->select('pd.id_pendaftaran', 'ps.nama_peserta', 'pd.status_pendaftaran', 'm.nama_mitra', 'a.peran_afiliasi', 'pd.tanggal_daftar', 'hb.skor_pretest', 'hb.skor_posttest')
            ->get();
            
        $responsDemografi = DB::table('respons_instrumen as r')
            ->join('pelaksanaan_instrumen as p', 'p.id_pelaksanaan', '=', 'r.id_pelaksanaan')
            ->where('p.id_kegiatan', $kegiatan->id_kegiatan)
            ->where('p.fase', 'demografi')
            ->where('r.is_final', true)
            ->pluck('r.id_pendaftaran');
            
        $responsKuesioner = DB::table('respons_instrumen as r')
            ->join('pelaksanaan_instrumen as p', 'p.id_pelaksanaan', '=', 'r.id_pelaksanaan')
            ->where('p.id_kegiatan', $kegiatan->id_kegiatan)
            ->where('p.fase', 'kuesioner')
            ->where('r.is_final', true)
            ->pluck('r.id_pendaftaran');

        $sertifikatTercetak = DB::table('sertifikat')
            ->whereIn('id_pendaftaran', $progres->pluck('id_pendaftaran'))
            ->pluck('id_pendaftaran');

        return view('admin.kegiatan.detail', [
            'k' => $kegiatan,
            'fasilitator' => Fasilitator::orderBy('nama_fasilitator')->get(),
            'materi' => Materi::orderBy('judul_materi')->get(),
            'versi' => VersiInstrumen::with('instrumen')->get(),
            'pendaftaran' => $kegiatan->pendaftaran()->with('peserta', 'afiliasi.mitra')->get(),
            'progres' => $progres,
            'responsDemografi' => $responsDemografi,
            'responsKuesioner' => $responsKuesioner,
            'sertifikatTercetak' => $sertifikatTercetak,
            'rekapHadir' => Kehadiran::selectRaw('id_sesi, count(*) as jumlah')
                ->whereIn('id_sesi', $kegiatan->sesi->pluck('id_sesi'))
                ->groupBy('id_sesi')->pluck('jumlah', 'id_sesi'),
            'butirKuesioner' => $kegiatan->konfigurasiEvaluasi
                ? $kegiatan->konfigurasiEvaluasi->versi->butir()->with('indikator')->get()
                : collect(),
        ]);
    }

    // ------------------------------------------------------------------- sesi

    public function simpanSesi(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate([
            'judul_sesi' => ['required', 'string', 'max:150'],
            'id_fasilitator' => ['required', 'integer', 'exists:fasilitator,id_fasilitator'],
            'tanggal_sesi' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'urutan_sesi' => ['required', 'integer', 'min:1'],
            'materi' => ['array'],
        ]);

        $sesi = Sesi::create(collect($data)->except('materi')->all() + ['id_kegiatan' => $kegiatan->id_kegiatan]);
        $sesi->materi()->sync($data['materi'] ?? []);

        return back()->with('sukses', 'Sesi ditambahkan.');
    }

    public function hapusSesi(Sesi $sesi)
    {
        $sesi->delete();

        return back()->with('sukses', 'Sesi dihapus.');
    }

    public function tugaskanFasilitator(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate([
            'id_fasilitator' => ['required', 'integer', 'exists:fasilitator,id_fasilitator'],
            'peran_penugasan' => ['required', 'string', 'max:50'],
        ]);

        $kegiatan->fasilitator()->syncWithoutDetaching([
            $data['id_fasilitator'] => ['peran_penugasan' => $data['peran_penugasan']],
        ]);

        return back()->with('sukses', 'Fasilitator ditugaskan.');
    }

    public function lepasFasilitator(Kegiatan $kegiatan, Fasilitator $fasilitator)
    {
        $kegiatan->fasilitator()->detach($fasilitator->id_fasilitator);

        return back()->with('sukses', 'Penugasan dilepas.');
    }

    // ------------------------------------------------- pelaksanaan & evaluasi

    public function simpanPelaksanaan(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate([
            'fase' => ['required', 'in:demografi,pretest,posttest,kuesioner'],
            'id_versi' => ['required', 'integer', 'exists:versi_instrumen,id_versi'],
            'dibuka_pada' => ['nullable', 'date'],
            'ditutup_pada' => ['nullable', 'date', 'after_or_equal:dibuka_pada'],
        ]);

        // Aturan bisnis #5: pre-test dan post-test wajib memakai versi yang sama.
        $pasangan = $data['fase'] === 'pretest' ? 'posttest' : ($data['fase'] === 'posttest' ? 'pretest' : null);
        if ($pasangan) {
            $lain = PelaksanaanInstrumen::where('id_kegiatan', $kegiatan->id_kegiatan)->where('fase', $pasangan)->first();
            if ($lain && (int) $lain->id_versi !== (int) $data['id_versi']) {
                return back()->withErrors(['id_versi' => 'Pre-test dan post-test harus memakai versi instrumen yang sama.']);
            }
        }

        PelaksanaanInstrumen::updateOrCreate(
            ['id_kegiatan' => $kegiatan->id_kegiatan, 'fase' => $data['fase']],
            collect($data)->except('fase')->all(),
        );

        $this->kunciVersi($data['id_versi']);

        return back()->with('sukses', 'Pelaksanaan instrumen fase '.$data['fase'].' ditetapkan.');
    }

    public function simpanEvaluasi(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate([
            'id_versi' => ['required', 'integer', 'exists:versi_instrumen,id_versi'],
            'mode_evaluasi' => ['required', 'in:identitas,anonim'],
            'dibuka_pada' => ['nullable', 'date'],
            'ditutup_pada' => ['nullable', 'date', 'after_or_equal:dibuka_pada'],
        ]);

        DB::transaction(function () use ($kegiatan, $data) {
            KonfigurasiEvaluasiKegiatan::updateOrCreate(['id_kegiatan' => $kegiatan->id_kegiatan], $data);

            // Kuesioner memakai mesin instrumen yang sama, jadi butuh baris pelaksanaan.
            PelaksanaanInstrumen::updateOrCreate(
                ['id_kegiatan' => $kegiatan->id_kegiatan, 'fase' => 'kuesioner'],
                ['id_versi' => $data['id_versi'], 'dibuka_pada' => $data['dibuka_pada'] ?? null, 'ditutup_pada' => $data['ditutup_pada'] ?? null],
            );

            $this->kunciVersi($data['id_versi']);
        });

        return back()->with('sukses', 'Konfigurasi kuesioner disimpan.');
    }

    public function simpanIndikator(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate([
            'indikator' => ['array'],
            'indikator.*' => ['nullable', 'in:materi,fasilitator,metode,fasilitas_platform,manfaat,kepuasan,saran'],
        ]);

        foreach ($data['indikator'] ?? [] as $idButir => $aspek) {
            if ($aspek) {
                IndikatorEvaluasi::updateOrCreate(['id_butir' => $idButir], ['aspek_dinilai' => $aspek]);
            } else {
                IndikatorEvaluasi::where('id_butir', $idButir)->delete();
            }
        }

        return back()->with('sukses', 'Pemetaan indikator evaluasi disimpan.');
    }

    public function ubahStatusPendaftaran(Request $request, \App\Models\Pendaftaran $pendaftaran)
    {
        $data = $request->validate([
            'status_pendaftaran' => ['required', 'in:terdaftar,hadir,tidak_hadir,dibatalkan'],
        ]);
        $pendaftaran->update($data);

        return back()->with('sukses', 'Status pendaftaran diubah.');
    }

    // ---------------------------------------------------------------- private

    public function toggleTampilkanHasil(Request $request, Kegiatan $kegiatan, string $fase)
    {
        abort_unless(in_array($fase, ['demografi', 'pretest', 'posttest']), 404);
        
        $pelaksanaan = PelaksanaanInstrumen::where('id_kegiatan', $kegiatan->id_kegiatan)->where('fase', $fase)->firstOrFail();
        $pelaksanaan->update(['tampilkan_hasil' => !$pelaksanaan->tampilkan_hasil]);

        return back()->with('sukses', 'Status tampilan hasil ' . $fase . ' diubah.');
    }

    public function terbitkanSertifikatMassal(Kegiatan $kegiatan)
    {
        $pendaftaran = $kegiatan->pendaftaran()->whereDoesntHave('sertifikat')->get();
        $jumlah = 0;

        foreach ($pendaftaran as $p) {
            if (\App\Support\Alur::layakSertifikat($p)) {
                \App\Models\Sertifikat::create([
                    'id_pendaftaran' => $p->id_pendaftaran,
                    'nomor_sertifikat' => sprintf('CAQ/%s/%05d', now()->year, $p->id_pendaftaran),
                    'kode_verifikasi' => 'VF-'.strtoupper(bin2hex(random_bytes(3))),
                ]);
                \App\Models\LogIntegrasi::catat('sertifikat', 'terbit_manual', 'pendaftaran #'.$p->id_pendaftaran);
                $jumlah++;
            }
        }

        return back()->with('sukses', "$jumlah sertifikat berhasil diterbitkan untuk pendaftar yang memenuhi syarat.");
    }
    
    public function buatTokenSesi(Sesi $sesi)
    {
        \App\Models\TokenQrSesi::create([
            'id_sesi' => $sesi->id_sesi,
            'dibuka_oleh' => auth()->id(),
            'token' => strtoupper(bin2hex(random_bytes(6))),
            'berlaku_hingga' => now()->addHours(12),
        ]);
        return back()->with('sukses', 'Token kehadiran sesi dibuat oleh Admin.');
    }

    public function demografi(Kegiatan $kegiatan)
    {
        $pelaksanaan = $kegiatan->pelaksanaan()->where('fase', 'demografi')->firstOrFail();
        
        $respons = \App\Models\ResponsInstrumen::where('id_pelaksanaan', $pelaksanaan->id_pelaksanaan)
            ->where('is_final', true)
            ->with('pendaftaran.peserta', 'jawaban.butir')
            ->get();

        return view('admin.kegiatan.demografi', [
            'k' => $kegiatan,
            'respons' => $respons,
            'butir' => $pelaksanaan->versi->butir()->orderBy('nomor_urut')->get()
        ]);
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'id_program' => ['required', 'integer', 'exists:program_pkm,id_program'],
            'id_sekolah' => ['required', 'integer', 'exists:sekolah,id_sekolah'],
            'id_lokasi' => ['nullable', 'integer', 'exists:lokasi,id_lokasi'],
            'tema' => ['required', 'string', 'max:150'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'mode_pelaksanaan' => ['required', 'in:luring,daring,hybrid'],
            'status_kegiatan' => ['required', 'in:terjadwal,berlangsung,selesai,dibatalkan'],
        ]);

        if ($data['mode_pelaksanaan'] !== 'daring' && empty($data['id_lokasi'])) {
            abort(422, 'Kegiatan luring/hybrid wajib memiliki lokasi.');
        }

        return $data;
    }

    private function kunciVersi(int $idVersi): void
    {
        $versi = VersiInstrumen::find($idVersi);
        if ($versi && $versi->status_versi === 'draft') {
            $versi->update(['status_versi' => 'terkunci', 'dikunci_pada' => now()]);
        }
    }
}
