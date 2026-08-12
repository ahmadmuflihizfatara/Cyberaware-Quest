<?php

namespace App\Http\Controllers;

use App\Models\Afiliasi;
use App\Models\AktivitasGamifikasi;
use App\Models\AktivitasPembelajaran;
use App\Models\ArtefakPeserta;
use App\Models\Badge;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\LogIntegrasi;
use App\Models\Mitra;
use App\Models\PartisipasiAktivitas;
use App\Models\PartisipasiGamifikasi;
use App\Models\Pendaftaran;
use App\Models\PenukaranReward;
use App\Models\Peserta;
use App\Models\Reward;
use App\Models\TokenQrSesi;
use App\Models\TransaksiPoin;
use App\Support\Alur;
use App\Support\MesinInstrumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PesertaController extends Controller
{
    // -------------------------------------------------------------- dashboard

    public function dashboard()
    {
        $peserta = $this->peserta();
        $pendaftaran = $this->daftarPendaftaran($peserta);
        $aktif = $pendaftaran->firstWhere(fn ($p) => $p->kegiatan->status_kegiatan !== 'selesai') ?? $pendaftaran->first();

        return view('peserta.dashboard', [
            'peserta' => $peserta,
            'pendaftaran' => $pendaftaran,
            'aktif' => $aktif,
            'tahapan' => $aktif ? Alur::tahapan($aktif) : [],
            'saldo' => $aktif?->saldoPoin() ?? 0,
            'badgeDidapat' => $aktif ? $aktif->badge()->count() : 0,
            'badgeTotal' => Badge::count(),
            'peringkat' => $aktif ? $this->peringkat($aktif) : null,
            'kegiatanTersedia' => Kegiatan::with('sekolah.mitra')
                ->whereNotIn('id_kegiatan', $pendaftaran->pluck('id_kegiatan'))
                ->whereIn('status_kegiatan', ['terjadwal', 'berlangsung'])
                ->orderBy('tanggal_mulai')->limit(5)->get(),
        ]);
    }

    public function kegiatanSaya()
    {
        return view('peserta.kegiatan', [
            'pendaftaran' => $this->daftarPendaftaran($this->peserta()),
        ]);
    }

    // -------------------------------------------------- informasi & pendaftaran kegiatan

    public function informasiKegiatan(Request $request)
    {
        $q = Kegiatan::with('program', 'sekolah.mitra', 'lokasi')->withCount('pendaftaran');

        if ($request->filled('mode')) {
            $q->where('mode_pelaksanaan', $request->string('mode'));
        }
        if ($request->filled('cari')) {
            $q->where('tema', 'ilike', '%'.$request->string('cari').'%');
        }

        return view('peserta.informasi-kegiatan', [
            'kegiatan' => $q->orderBy('tanggal_mulai')->paginate(9)->withQueryString(),
        ]);
    }

    public function informasiKegiatanShow(Kegiatan $kegiatan)
    {
        $kegiatan->load('program', 'sekolah.mitra', 'lokasi', 'sesi.fasilitator', 'sesi.materi');

        $peserta = $this->peserta();
        $sudahDaftar = $peserta && Pendaftaran::where('id_peserta', $peserta->id_peserta)
            ->where('id_kegiatan', $kegiatan->id_kegiatan)->exists();

        return view('peserta.informasi-kegiatan-show', [
            'kegiatan' => $kegiatan,
            'sudahDaftar' => $sudahDaftar,
            'mitra' => Mitra::where('status_mitra', 'aktif')->orderBy('nama_mitra')->get(),
        ]);
    }

    public function daftar(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate([
            'nama_peserta' => ['required', 'string', 'max:150'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'id_mitra' => ['required', 'integer', 'exists:mitra,id_mitra'],
            'peran_afiliasi' => ['required', 'in:siswa,guru,staf,umum'],
            'setuju' => ['accepted'],
        ]);

        if ($kegiatan->sisaKuota() < 1) {
            return back()->withErrors(['id_mitra' => 'Kuota kegiatan sudah penuh.'])->withInput();
        }
        if ($kegiatan->status_kegiatan === 'dibatalkan') {
            return back()->withErrors(['id_mitra' => 'Kegiatan ini dibatalkan.'])->withInput();
        }

        $pengguna = $request->user();

        $pendaftaran = DB::transaction(function () use ($data, $kegiatan, $pengguna) {
            $peserta = Peserta::firstOrCreate(
                ['email' => $pengguna->email],
                ['nama_peserta' => $data['nama_peserta'], 'no_hp' => $data['no_hp'] ?? null],
            );

            if (Pendaftaran::where('id_peserta', $peserta->id_peserta)
                ->where('id_kegiatan', $kegiatan->id_kegiatan)->exists()) {
                throw ValidationException::withMessages([
                    'nama_peserta' => 'Anda sudah terdaftar pada kegiatan ini.',
                ]);
            }

            $pendaftaran = Pendaftaran::create([
                'id_peserta' => $peserta->id_peserta,
                'id_kegiatan' => $kegiatan->id_kegiatan,
            ]);

            Afiliasi::create([
                'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                'id_mitra' => $data['id_mitra'],
                'peran_afiliasi' => $data['peran_afiliasi'],
            ]);

            return $pendaftaran;
        });

        LogIntegrasi::catat('pendaftaran', 'daftar_kegiatan', 'kegiatan #'.$kegiatan->id_kegiatan);

        return redirect()->route('peserta.pendaftaran.show', $pendaftaran)
            ->with('sukses', 'Pendaftaran berhasil. Lanjutkan dengan persetujuan pengolahan data.');
    }

    public function show(Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);
        $pendaftaran->load('kegiatan.sekolah.mitra', 'kegiatan.sesi.materi', 'afiliasi.mitra');

        return view('peserta.pendaftaran', [
            'p' => $pendaftaran,
            'tahapan' => Alur::tahapan($pendaftaran),
        ]);
    }

    // ------------------------------------------------------- tahap 1: consent

    public function persetujuan(Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);

        return view('peserta.persetujuan', ['p' => $pendaftaran]);
    }

    public function simpanPersetujuan(Request $request, Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);
        $request->validate(['setuju' => ['accepted']]);

        $pendaftaran->persetujuan()->updateOrCreate([], [
            'versi_kebijakan' => '1.0',
            'disetujui' => true,
            'waktu_persetujuan' => now(),
        ]);

        return redirect()->route('peserta.instrumen', [$pendaftaran, 'demografi'])
            ->with('sukses', 'Persetujuan tercatat. Lanjutkan mengisi data demografi.');
    }

    // ------------------------------- tahap 1/2/4/5: mesin instrumen (generik)

    public function instrumen(Pendaftaran $pendaftaran, string $fase)
    {
        $this->pastikanMilik($pendaftaran);
        abort_unless(in_array($fase, ['demografi', 'pretest', 'posttest', 'kuesioner'], true), 404);

        if ($alasan = Alur::alasanTerkunci($pendaftaran, $this->kunciTahap($fase))) {
            return redirect()->route('peserta.pendaftaran.show', $pendaftaran)->withErrors(['alur' => $alasan]);
        }

        $pelaksanaan = MesinInstrumen::pelaksanaan($pendaftaran, $fase);
        if (! $pelaksanaan) {
            return redirect()->route('peserta.pendaftaran.show', $pendaftaran)
                ->withErrors(['alur' => 'Panitia belum menetapkan instrumen untuk tahap '.$fase.'.']);
        }
        if (! $pelaksanaan->terbuka()) {
            return redirect()->route('peserta.pendaftaran.show', $pendaftaran)
                ->withErrors(['alur' => 'Tahap '.$fase.' belum dibuka atau sudah ditutup.']);
        }

        $final = $pendaftaran->responsFinal($fase);
        if ($final) {
            if (in_array($fase, ['pretest', 'posttest'], true) && ! $pelaksanaan->tampilkan_hasil) {
                return view('peserta.instrumen-menunggu-hasil', ['p' => $pendaftaran, 'fase' => $fase]);
            }

            return view('peserta.instrumen-hasil', [
                'p' => $pendaftaran, 'fase' => $fase, 'respons' => $final->load('penilaian'),
                'hasilBelajar' => $this->hasilBelajar($pendaftaran),
            ]);
        }

        return view('peserta.instrumen', [
            'p' => $pendaftaran,
            'fase' => $fase,
            'pelaksanaan' => $pelaksanaan,
            'butir' => $pelaksanaan->versi->butir()->with('opsi')->get(),
            'anonim' => $fase === 'kuesioner'
                && $pendaftaran->kegiatan->konfigurasiEvaluasi?->mode_evaluasi === 'anonim',
        ]);
    }

    public function kirimInstrumen(Request $request, Pendaftaran $pendaftaran, string $fase)
    {
        $this->pastikanMilik($pendaftaran);
        abort_unless(in_array($fase, ['demografi', 'pretest', 'posttest', 'kuesioner'], true), 404);

        if ($alasan = Alur::alasanTerkunci($pendaftaran, $this->kunciTahap($fase))) {
            return back()->withErrors(['alur' => $alasan]);
        }

        $pelaksanaan = MesinInstrumen::pelaksanaan($pendaftaran, $fase);
        abort_unless($pelaksanaan && $pelaksanaan->terbuka(), 422, 'Tahap ini tidak terbuka.');

        $respons = MesinInstrumen::responsBerjalan($pendaftaran, $pelaksanaan);
        MesinInstrumen::kirim($respons, $request->input('jawaban', []));

        LogIntegrasi::catat('instrumen', 'kirim_'.$fase, 'pendaftaran #'.$pendaftaran->id_pendaftaran);

        if ($fase === 'kuesioner' && Alur::layakSertifikat($pendaftaran)) {
            $this->terbitkanSertifikat($pendaftaran);
        }

        return redirect()->route('peserta.instrumen', [$pendaftaran, $fase])
            ->with('sukses', 'Jawaban tersimpan sebagai respons final.');
    }

    // ------------------------------------------- tahap 3: check-in dan materi

    public function checkin(Request $request, Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);

        return view('peserta.checkin', [
            'p' => $pendaftaran->load('kegiatan.sesi'),
            'token' => $request->query('token', ''),
            'hadir' => $pendaftaran->kehadiran()->pluck('id_sesi')->all(),
        ]);
    }

    public function simpanCheckin(Request $request, Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);
        $request->validate(['token' => ['required', 'string', 'max:64']]);

        $token = TokenQrSesi::with('sesi')->where('token', $request->string('token'))->first();

        if (! $token) {
            return back()->withErrors(['token' => 'Token tidak dikenali.']);
        }
        if (! $token->masihBerlaku()) {
            return back()->withErrors(['token' => 'Token sudah kedaluwarsa. Minta presensi manual kepada fasilitator.']);
        }
        if ($token->sesi->id_kegiatan !== $pendaftaran->id_kegiatan) {
            return back()->withErrors(['token' => 'Token ini milik kegiatan lain.']);
        }

        $sudah = Kehadiran::where('id_pendaftaran', $pendaftaran->id_pendaftaran)
            ->where('id_sesi', $token->id_sesi)->exists();

        if ($sudah) {
            return back()->with('sukses', 'Kehadiran Anda pada sesi ini sudah tercatat sebelumnya.');
        }

        Kehadiran::create([
            'id_pendaftaran' => $pendaftaran->id_pendaftaran,
            'id_sesi' => $token->id_sesi,
            'id_token' => $token->id_token,
            'metode_presensi' => 'qr',
        ]);
        $pendaftaran->update(['status_pendaftaran' => 'hadir']);
        LogIntegrasi::catat('kehadiran', 'checkin_qr', 'sesi #'.$token->id_sesi);

        return back()->with('sukses', 'Check-in berhasil untuk sesi '.$token->sesi->judul_sesi.'.');
    }

    public function materi(Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);

        return view('peserta.materi', [
            'p' => $pendaftaran->load('kegiatan.sesi.materi', 'kegiatan.sesi.fasilitator'),
            'hadir' => $pendaftaran->kehadiran()->pluck('id_sesi')->all(),
        ]);
    }

    public function aktivitas(Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);

        $aktivitas = AktivitasPembelajaran::whereIn('id_sesi', $pendaftaran->kegiatan->sesi->pluck('id_sesi'))
            ->with('sesi')->get();

        return view('peserta.aktivitas', [
            'p' => $pendaftaran,
            'aktivitas' => $aktivitas,
            'partisipasi' => PartisipasiAktivitas::with('artefak')
                ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                ->get()->keyBy('id_aktivitas'),
        ]);
    }

    public function simpanArtefak(Request $request, Pendaftaran $pendaftaran, AktivitasPembelajaran $aktivitas)
    {
        $this->pastikanMilik($pendaftaran);

        $data = $request->validate([
            'judul_artefak' => ['required', 'string', 'max:150'],
            'tipe_file' => ['required', 'in:image,pdf,video,link,document'],
            'tautan' => ['required_without:berkas', 'nullable', 'url', 'max:255'],
            'berkas' => ['required_without:tautan', 'nullable', 'file', 'max:20480',
                'mimes:jpg,jpeg,png,webp,pdf,mp4,doc,docx,ppt,pptx'],
        ], [], ['berkas' => 'file artefak']);

        $partisipasi = PartisipasiAktivitas::firstOrCreate(
            ['id_aktivitas' => $aktivitas->id_aktivitas, 'id_pendaftaran' => $pendaftaran->id_pendaftaran],
            ['status_partisipasi' => 'berlangsung'],
        );

        $ukuran = null;
        if ($request->hasFile('berkas')) {
            $path = $request->file('berkas')->store('artefak', 'public');
            $tautan = '/storage/'.$path;
            $ukuran = (int) ceil($request->file('berkas')->getSize() / 1024);
        } else {
            $tautan = $data['tautan'];
        }

        ArtefakPeserta::updateOrCreate(
            ['id_partisipasi' => $partisipasi->id_partisipasi],
            [
                'judul_artefak' => $data['judul_artefak'],
                'tautan_atau_file' => $tautan,
                'tipe_file' => $data['tipe_file'],
                'ukuran_file_kb' => $ukuran,
                'status_verifikasi' => 'menunggu',
                'catatan_revisi' => null,
                'diunggah_pada' => now(),
            ],
        );

        $partisipasi->update(['status_partisipasi' => 'selesai', 'waktu_selesai' => now()]);
        LogIntegrasi::catat('aktivitas', 'unggah_artefak', 'aktivitas #'.$aktivitas->id_aktivitas);

        return back()->with('sukses', 'Artefak terkirim dan menunggu verifikasi fasilitator.');
    }

    // -------------------------------------------------- tahap 3: gamifikasi

    public function gamifikasi(Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);

        $daftar = AktivitasGamifikasi::whereIn('id_sesi', $pendaftaran->kegiatan->sesi->pluck('id_sesi'))
            ->with('sesi')->get();

        return view('peserta.gamifikasi', [
            'p' => $pendaftaran,
            'daftar' => $daftar,
            'partisipasi' => PartisipasiGamifikasi::where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                ->get()->keyBy('id_gamifikasi'),
            'leaderboard' => $this->leaderboard($pendaftaran),
            'saldo' => $pendaftaran->saldoPoin(),
        ]);
    }

    public function ikutGamifikasi(Request $request, Pendaftaran $pendaftaran, AktivitasGamifikasi $gamifikasi)
    {
        $this->pastikanMilik($pendaftaran);
        abort_unless($gamifikasi->sesi->id_kegiatan === $pendaftaran->id_kegiatan, 403);

        $benar = (int) $request->input('benar', 0);
        $totalSoal = max(1, (int) $request->input('total', 1));
        $poin = (int) round($gamifikasi->poin_maksimal * min(1, $benar / $totalSoal));

        try {
            DB::transaction(function () use ($pendaftaran, $gamifikasi, $poin) {
                // uq (id_gamifikasi, id_pendaftaran) menjaga poin tidak diberikan dua kali.
                $partisipasi = PartisipasiGamifikasi::create([
                    'id_gamifikasi' => $gamifikasi->id_gamifikasi,
                    'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                    'skor_permainan' => $poin,
                    'waktu_selesai' => now(),
                ]);

                if ($poin > 0) {
                    TransaksiPoin::create([
                        'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                        'id_partisipasi_gamifikasi' => $partisipasi->id_partisipasi_g,
                        'jenis_transaksi' => 'perolehan',
                        'jumlah_poin' => $poin,
                        'keterangan' => $gamifikasi->judul_gamifikasi,
                    ]);
                }
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return back()->withErrors(['gamifikasi' => 'Aktivitas ini sudah pernah Anda selesaikan; poin tidak diberikan ulang.']);
        }

        return back()->with('sukses', 'Aktivitas selesai. Anda memperoleh '.$poin.' poin.');
    }

    public function leaderboardHalaman(Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);

        return view('peserta.leaderboard', [
            'p' => $pendaftaran,
            'leaderboard' => $this->leaderboard($pendaftaran),
        ]);
    }

    // ------------------------------------------------------- poin dan reward

    public function poin()
    {
        $pendaftaran = $this->daftarPendaftaran($this->peserta());

        return view('peserta.poin', [
            'pendaftaran' => $pendaftaran,
            'mutasi' => TransaksiPoin::whereIn('id_pendaftaran', $pendaftaran->pluck('id_pendaftaran'))
                ->orderByDesc('id_transaksi')->get(),
            'saldo' => (int) TransaksiPoin::whereIn('id_pendaftaran', $pendaftaran->pluck('id_pendaftaran'))
                ->sum('jumlah_poin'),
        ]);
    }

    public function reward()
    {
        $pendaftaran = $this->daftarPendaftaran($this->peserta());
        $aktif = $pendaftaran->first();

        return view('peserta.reward', [
            'reward' => Reward::where('status_aktif', true)->orderBy('biaya_poin')->get(),
            'aktif' => $aktif,
            'saldo' => $aktif?->saldoPoin() ?? 0,
            'riwayat' => PenukaranReward::with('reward')
                ->whereIn('id_pendaftaran', $pendaftaran->pluck('id_pendaftaran'))
                ->orderByDesc('id_penukaran')->get(),
        ]);
    }

    public function tukarReward(Request $request, Reward $reward)
    {
        $pendaftaran = Pendaftaran::findOrFail($request->integer('id_pendaftaran'));
        $this->pastikanMilik($pendaftaran);

        try {
            DB::transaction(function () use ($reward, $pendaftaran) {
                $terkunci = Reward::where('id_reward', $reward->id_reward)->lockForUpdate()->first();

                abort_if(! $terkunci->status_aktif, 422, 'Reward tidak aktif.');
                abort_if($terkunci->stok < 1, 422, 'Stok reward habis.');

                $saldo = (int) TransaksiPoin::where('id_pendaftaran', $pendaftaran->id_pendaftaran)->sum('jumlah_poin');
                abort_if($saldo < $terkunci->biaya_poin, 422, 'Saldo poin tidak mencukupi.');

                $penukaran = PenukaranReward::create([
                    'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                    'id_reward' => $terkunci->id_reward,
                    'biaya_poin_saat_itu' => $terkunci->biaya_poin,
                ]);

                TransaksiPoin::create([
                    'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                    'id_penukaran' => $penukaran->id_penukaran,
                    'jenis_transaksi' => 'penukaran',
                    'jumlah_poin' => -1 * $terkunci->biaya_poin,
                    'keterangan' => 'Penukaran '.$terkunci->nama_reward,
                ]);

                $terkunci->decrement('stok');
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withErrors(['reward' => $e->getMessage()]);
        }

        LogIntegrasi::catat('reward', 'penukaran', $reward->nama_reward);

        return back()->with('sukses', 'Penukaran berhasil diproses.');
    }

    public function badge()
    {
        $pendaftaran = $this->daftarPendaftaran($this->peserta());
        $dimiliki = DB::table('badge_peserta')
            ->whereIn('id_pendaftaran', $pendaftaran->pluck('id_pendaftaran'))
            ->pluck('id_badge')->unique();

        return view('peserta.badge', [
            'badge' => Badge::orderBy('id_badge')->get(),
            'dimiliki' => $dimiliki,
        ]);
    }

    // ------------------------------------------------------------- sertifikat

    public function sertifikat(Pendaftaran $pendaftaran)
    {
        $this->pastikanMilik($pendaftaran);

        if (! $pendaftaran->sertifikat && Alur::layakSertifikat($pendaftaran)) {
            $this->terbitkanSertifikat($pendaftaran);
        }

        return view('peserta.sertifikat', [
            'p' => $pendaftaran->fresh(['sertifikat', 'peserta', 'kegiatan.sekolah.mitra']),
            'layak' => Alur::layakSertifikat($pendaftaran),
        ]);
    }

    // ----------------------------------------------------------------- profil

    public function profil()
    {
        return view('peserta.profil', ['peserta' => $this->peserta()]);
    }

    public function simpanProfil(Request $request)
    {
        $pengguna = $request->user();

        $data = $request->validate([
            'nama_pengguna' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('pengguna', 'email')->ignore($pengguna->id_pengguna, 'id_pengguna')],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'npm' => ['nullable', 'string', 'max:50'],
            'asal_sekolah' => ['nullable', 'string', 'max:150'],
            'alamat_domisili' => ['nullable', 'string', 'max:1000'],
            'no_ktp' => ['nullable', 'string', 'max:50'],
            'foto_profil' => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $pathFoto = $request->hasFile('foto_profil')
            ? $request->file('foto_profil')->store('profil', 'public')
            : null;

        DB::transaction(function () use ($pengguna, $data, $pathFoto) {
            $lama = $pengguna->email;
            $pengguna->update(array_filter([
                'nama_pengguna' => $data['nama_pengguna'],
                'email' => $data['email'],
                'kata_sandi_hash' => ($data['password'] ?? null) ? Hash::make($data['password']) : null,
            ]));

            Peserta::where('email', $lama)->update(array_merge([
                'nama_peserta' => $data['nama_pengguna'],
                'email' => $data['email'],
                'no_hp' => $data['no_hp'] ?? null,
                'npm' => $data['npm'] ?? null,
                'asal_sekolah' => $data['asal_sekolah'] ?? null,
                'alamat_domisili' => $data['alamat_domisili'] ?? null,
                'no_ktp' => $data['no_ktp'] ?? null,
            ], $pathFoto ? ['foto_profil' => $pathFoto] : []));
        });

        return back()->with('sukses', 'Profil diperbarui.');
    }

    // ---------------------------------------------------------------- private

    private function peserta(): ?Peserta
    {
        return Peserta::where('email', auth()->user()->email)->first();
    }

    private function daftarPendaftaran(?Peserta $peserta)
    {
        if (! $peserta) {
            return collect();
        }

        return Pendaftaran::with('kegiatan.sekolah.mitra')
            ->where('id_peserta', $peserta->id_peserta)
            ->orderByDesc('id_pendaftaran')->get();
    }

    private function pastikanMilik(Pendaftaran $pendaftaran): void
    {
        $peserta = $this->peserta();
        abort_unless($peserta && $pendaftaran->id_peserta === $peserta->id_peserta, 403,
            'Pendaftaran ini bukan milik akun Anda.');
    }

    private function kunciTahap(string $fase): string
    {
        return match ($fase) {
            'demografi' => 'persetujuan',
            default => $fase,
        };
    }

    private function hasilBelajar(Pendaftaran $p): ?object
    {
        return DB::table('v_hasil_belajar')->where('id_pendaftaran', $p->id_pendaftaran)->first();
    }

    private function leaderboard(Pendaftaran $p)
    {
        $idKegiatan = $p->id_kegiatan;

        return DB::table('v_leaderboard as l')
            ->join('pendaftaran as pd', 'pd.id_pendaftaran', '=', 'l.id_pendaftaran')
            ->where('pd.id_kegiatan', $idKegiatan)
            ->orderByDesc('l.total_poin_diperoleh')
            ->select('l.*')->limit(20)->get();
    }

    private function peringkat(Pendaftaran $p): ?int
    {
        $baris = $this->leaderboard($p);
        foreach ($baris->values() as $i => $b) {
            if ((int) $b->id_pendaftaran === $p->id_pendaftaran) {
                return $i + 1;
            }
        }

        return null;
    }

    private function terbitkanSertifikat(Pendaftaran $p): void
    {
        if ($p->sertifikat) {
            return;
        }

        \App\Models\Sertifikat::create([
            'id_pendaftaran' => $p->id_pendaftaran,
            'nomor_sertifikat' => sprintf('CAQ/%s/%05d', now()->year, $p->id_pendaftaran),
            'kode_verifikasi' => 'VF-'.strtoupper(bin2hex(random_bytes(3))),
        ]);

        LogIntegrasi::catat('sertifikat', 'terbit_otomatis', 'pendaftaran #'.$p->id_pendaftaran);
    }
}
