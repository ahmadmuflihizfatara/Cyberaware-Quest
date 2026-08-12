<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\ProgramPkm;
use App\Models\Sertifikat;
use Illuminate\Http\Request;

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

        return view('publik.kegiatan-detail', compact('kegiatan', 'sudahDaftar'));
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
