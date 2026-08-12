<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fasilitator;
use App\Models\IndikatorEvaluasi;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\KonfigurasiEvaluasiKegiatan;
use App\Models\LogIntegrasi;
use App\Models\Lokasi;
use App\Models\Materi;
use App\Models\PelaksanaanInstrumen;
use App\Models\Pendaftaran;
use App\Models\ProgramPkm;
use App\Models\ResponsInstrumen;
use App\Models\Sekolah;
use App\Models\Sertifikat;
use App\Models\Sesi;
use App\Models\TokenQrSesi;
use App\Models\VersiInstrumen;
use App\Support\Alur;
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

    public function show(Request $request, Kegiatan $kegiatan)
    {
        $kegiatan->load('program', 'sekolah.mitra', 'lokasi', 'sesi.fasilitator', 'sesi.materi',
            'fasilitator', 'pelaksanaan.versi.instrumen', 'konfigurasiEvaluasi.versi');

        $q = $kegiatan->pendaftaran()->with('peserta', 'afiliasi.mitra', 'sertifikat')->withCount('kehadiran')
            ->leftJoin('v_hasil_belajar as hb', 'hb.id_pendaftaran', '=', 'pendaftaran.id_pendaftaran')
            ->addSelect('pendaftaran.*', 'hb.skor_pretest', 'hb.skor_posttest');

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $q->whereHas('peserta', fn ($x) => $x->where('nama_peserta', 'ilike', '%'.$cari.'%'));
        }
        if ($request->filled('status')) {
            $q->where('status_pendaftaran', $request->string('status'));
        }

        $pendaftaran = $q->orderByDesc('pendaftaran.id_pendaftaran')->paginate(10)->withQueryString();

        // Satu kueri gabungan untuk seluruh baris di halaman ini — menghindari
        // N+1 dari memanggil Alur::tahapan() satu per satu per pendaftar.
        $faseSelesai = DB::table('respons_instrumen as r')
            ->join('pelaksanaan_instrumen as pi', 'pi.id_pelaksanaan', '=', 'r.id_pelaksanaan')
            ->where('pi.id_kegiatan', $kegiatan->id_kegiatan)
            ->where('r.is_final', true)
            ->whereIn('r.id_pendaftaran', $pendaftaran->pluck('id_pendaftaran'))
            ->select('r.id_pendaftaran', 'pi.fase')
            ->get()
            ->groupBy('id_pendaftaran')
            ->map(fn ($baris) => $baris->pluck('fase')->all());

        return view('admin.kegiatan.detail', [
            'k' => $kegiatan,
            'fasilitator' => Fasilitator::orderBy('nama_fasilitator')->get(),
            'materi' => Materi::orderBy('judul_materi')->get(),
            'versi' => VersiInstrumen::with('instrumen')->get(),
            'pendaftaran' => $pendaftaran,
            'faseSelesai' => $faseSelesai,
            'totalSesi' => $kegiatan->sesi->count(),
            'rekapHadir' => Kehadiran::selectRaw('id_sesi, count(*) as jumlah')
                ->whereIn('id_sesi', $kegiatan->sesi->pluck('id_sesi'))
                ->groupBy('id_sesi')->pluck('jumlah', 'id_sesi'),
            'butirKuesioner' => $kegiatan->konfigurasiEvaluasi
                ? $kegiatan->konfigurasiEvaluasi->versi->butir()->with('indikator')->get()
                : collect(),
        ]);
    }

    /** Rekap jawaban demografi seluruh peserta kegiatan ini, untuk ditinjau admin. */
    public function demografi(Kegiatan $kegiatan)
    {
        $pelaksanaan = $kegiatan->pelaksanaan()->where('fase', 'demografi')->firstOrFail();

        $respons = ResponsInstrumen::where('id_pelaksanaan', $pelaksanaan->id_pelaksanaan)
            ->where('is_final', true)
            ->with('pendaftaran.peserta', 'jawaban.butir', 'jawaban.opsi')
            ->get();

        return view('admin.kegiatan.demografi', [
            'k' => $kegiatan,
            'respons' => $respons,
            'butir' => $pelaksanaan->versi->butir()->orderBy('nomor_urut')->get(),
        ]);
    }

    /** Buka/tutup tampilnya hasil pretest/posttest ke peserta untuk satu fase. */
    public function toggleTampilkanHasil(Kegiatan $kegiatan, string $fase)
    {
        abort_unless(in_array($fase, ['pretest', 'posttest'], true), 404);

        $pelaksanaan = PelaksanaanInstrumen::where('id_kegiatan', $kegiatan->id_kegiatan)
            ->where('fase', $fase)->firstOrFail();
        $pelaksanaan->update(['tampilkan_hasil' => ! $pelaksanaan->tampilkan_hasil]);

        LogIntegrasi::catat('kegiatan', 'toggle_tampilkan_hasil', $fase.' kegiatan #'.$kegiatan->id_kegiatan);

        return back()->with('sukses', 'Status tampilan hasil '.$fase.' diubah.');
    }

    /**
     * Alat bantu tambahan: terbitkan sertifikat untuk semua pendaftar yang
     * sudah memenuhi syarat tapi entah kenapa belum kebagian (mis. jawaban
     * dikirim sebelum fitur auto-terbit ada). Auto-terbit di kirimInstrumen()
     * tetap jalan seperti biasa — ini cuma jaring pengaman manual.
     */
    public function terbitkanSertifikatMassal(Kegiatan $kegiatan)
    {
        $jumlah = 0;
        foreach ($kegiatan->pendaftaran()->whereDoesntHave('sertifikat')->get() as $p) {
            if (! Alur::layakSertifikat($p)) {
                continue;
            }
            Sertifikat::create([
                'id_pendaftaran' => $p->id_pendaftaran,
                'nomor_sertifikat' => sprintf('CAQ/%s/%05d', now()->year, $p->id_pendaftaran),
                'kode_verifikasi' => 'VF-'.strtoupper(bin2hex(random_bytes(3))),
            ]);
            $jumlah++;
        }
        LogIntegrasi::catat('sertifikat', 'terbit_massal_kegiatan', $jumlah.' sertifikat kegiatan #'.$kegiatan->id_kegiatan);

        return back()->with('sukses', $jumlah > 0
            ? $jumlah.' sertifikat diterbitkan untuk pendaftar yang memenuhi syarat.'
            : 'Tidak ada pendaftar tambahan yang memenuhi syarat sertifikat saat ini.');
    }

    /** Admin membuka token check-in untuk sesi manapun, tanpa terikat penugasan fasilitator. */
    public function buatTokenSesi(Request $request, Sesi $sesi)
    {
        $menit = max(1, min(720, $request->integer('menit', 60)));

        TokenQrSesi::create([
            'id_sesi' => $sesi->id_sesi,
            'dibuka_oleh' => $request->user()->id_pengguna,
            'token' => strtoupper(bin2hex(random_bytes(6))),
            'berlaku_hingga' => now()->addMinutes($menit),
        ]);
        LogIntegrasi::catat('kehadiran', 'buat_token_qr_admin', 'sesi #'.$sesi->id_sesi);

        return back()->with('sukses', 'Token check-in dibuat oleh admin, berlaku '.$menit.' menit.');
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

    public function ubahStatusPendaftaran(Request $request, Pendaftaran $pendaftaran)
    {
        $data = $request->validate([
            'status_pendaftaran' => ['required', 'in:terdaftar,hadir,tidak_hadir,dibatalkan'],
        ]);
        $pendaftaran->update($data);

        if ($request->wantsJson()) {
            return response()->json(['sukses' => true, 'status_pendaftaran' => $data['status_pendaftaran']]);
        }

        return back()->with('sukses', 'Status pendaftaran diubah.');
    }

    public function ubahStatusPendaftaranMassal(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate([
            'id_pendaftaran' => ['required', 'array', 'min:1'],
            'id_pendaftaran.*' => ['integer'],
            'status_pendaftaran' => ['required', 'in:terdaftar,hadir,tidak_hadir,dibatalkan'],
        ]);

        // Dibatasi ke kegiatan ini saja — mencegah id_pendaftaran kegiatan lain
        // ikut terubah lewat payload yang dimanipulasi.
        $jumlah = Pendaftaran::where('id_kegiatan', $kegiatan->id_kegiatan)
            ->whereIn('id_pendaftaran', $data['id_pendaftaran'])
            ->update(['status_pendaftaran' => $data['status_pendaftaran']]);

        if ($request->wantsJson()) {
            return response()->json(['sukses' => true, 'jumlah' => $jumlah, 'status_pendaftaran' => $data['status_pendaftaran']]);
        }

        return back()->with('sukses', $jumlah.' status pendaftaran diubah.');
    }

    // ---------------------------------------------------------------- private

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
