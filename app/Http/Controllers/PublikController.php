<?php

namespace App\Http\Controllers;

use App\Models\Afiliasi;
use App\Models\Kegiatan;
use App\Models\LogIntegrasi;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\ProgramPkm;
use App\Models\Sertifikat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublikController extends Controller
{
    public function beranda()
    {
        return view('publik.beranda', [
            'program' => ProgramPkm::orderByDesc('id_program')->limit(3)->get(),
            'kegiatan' => Kegiatan::with('program', 'sekolah.mitra')
                ->whereIn('status_kegiatan', ['terjadwal', 'berlangsung'])
                ->orderBy('tanggal_mulai')->limit(6)->get(),
        ]);
    }

    public function program()
    {
        return view('publik.program', [
            'program' => ProgramPkm::withCount('kegiatan')->orderByDesc('id_program')->get(),
        ]);
    }

    public function programShow(ProgramPkm $program)
    {
        $program->load('mitra', 'kegiatan.sekolah.mitra');

        return view('publik.program-detail', compact('program'));
    }

    public function kegiatan(Request $request)
    {
        $q = Kegiatan::with('program', 'sekolah.mitra', 'lokasi')->withCount('pendaftaran');

        if ($request->filled('mode')) {
            $q->where('mode_pelaksanaan', $request->string('mode'));
        }
        if ($request->filled('cari')) {
            $q->where('tema', 'ilike', '%'.$request->string('cari').'%');
        }

        return view('publik.kegiatan', [
            'kegiatan' => $q->orderBy('tanggal_mulai')->paginate(9)->withQueryString(),
        ]);
    }

    public function kegiatanShow(Kegiatan $kegiatan)
    {
        $kegiatan->load('program', 'sekolah.mitra', 'lokasi', 'sesi.fasilitator', 'sesi.materi');

        $sudahDaftar = false;
        if ($peserta = $this->pesertaSaatIni()) {
            $sudahDaftar = Pendaftaran::where('id_peserta', $peserta->id_peserta)
                ->where('id_kegiatan', $kegiatan->id_kegiatan)->exists();
        }

        $mitra = \App\Models\Mitra::where('status_mitra', 'aktif')->orderBy('nama_mitra')->get();

        return view('publik.kegiatan-detail', compact('kegiatan', 'sudahDaftar', 'mitra'));
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
                abort(422, 'Anda sudah terdaftar pada kegiatan ini.');
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

    public function verifikasi(Request $request)
    {
        $kode = trim((string) $request->query('kode', ''));
        $sertifikat = $kode === '' ? null
            : Sertifikat::with('pendaftaran.peserta', 'pendaftaran.kegiatan')
                ->where('kode_verifikasi', $kode)->first();

        return view('publik.verifikasi', compact('kode', 'sertifikat'));
    }

    private function pesertaSaatIni(): ?Peserta
    {
        $pengguna = auth()->user();

        return $pengguna ? Peserta::where('email', $pengguna->email)->first() : null;
    }
}
