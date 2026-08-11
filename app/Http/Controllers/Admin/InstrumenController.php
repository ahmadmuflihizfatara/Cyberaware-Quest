<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ButirInstrumen;
use App\Models\Instrumen;
use App\Models\OpsiButir;
use App\Models\PelaksanaanInstrumen;
use App\Models\VersiInstrumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstrumenController extends Controller
{
    public function versi(Instrumen $instrumen)
    {
        return view('admin.instrumen.versi', [
            'i' => $instrumen,
            'versi' => $instrumen->versi()->withCount('butir')->get(),
        ]);
    }

    public function simpanVersi(Instrumen $instrumen)
    {
        $nomor = 1 + (int) $instrumen->versi()->max('nomor_versi');
        VersiInstrumen::create(['id_instrumen' => $instrumen->id_instrumen, 'nomor_versi' => $nomor]);

        return back()->with('sukses', 'Versi '.$nomor.' dibuat sebagai draft.');
    }

    public function butir(VersiInstrumen $versi)
    {
        return view('admin.instrumen.butir', [
            'v' => $versi->load('instrumen'),
            'butir' => $versi->butir()->with('opsi')->get(),
            'terpakai' => PelaksanaanInstrumen::where('id_versi', $versi->id_versi)->exists(),
        ]);
    }

    public function simpanButir(Request $request, VersiInstrumen $versi)
    {
        $this->pastikanDraft($versi);

        $data = $request->validate([
            'teks_butir' => ['required', 'string'],
            'tipe_butir' => ['required', 'in:pilihan_ganda,esai,skala_likert,isian_singkat'],
            'bobot_skor' => ['required', 'numeric', 'min:0', 'max:999'],
            'wajib_diisi' => ['boolean'],
            'opsi' => ['array'],
            'opsi.*.teks' => ['nullable', 'string', 'max:255'],
            'opsi.*.nilai' => ['nullable', 'numeric'],
            'kunci' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($versi, $data, $request) {
            $butir = ButirInstrumen::create([
                'id_versi' => $versi->id_versi,
                'nomor_urut' => 1 + (int) $versi->butir()->max('nomor_urut'),
                'teks_butir' => $data['teks_butir'],
                'tipe_butir' => $data['tipe_butir'],
                'bobot_skor' => $data['bobot_skor'],
                'wajib_diisi' => $request->boolean('wajib_diisi'),
            ]);

            $urut = 1;
            foreach ($data['opsi'] ?? [] as $i => $o) {
                if (blank($o['teks'] ?? null)) {
                    continue;
                }
                OpsiButir::create([
                    'id_butir' => $butir->id_butir,
                    'teks_opsi' => $o['teks'],
                    'nilai_skor' => $o['nilai'] ?? 0,
                    'kunci_jawaban' => (int) ($data['kunci'] ?? -1) === (int) $i,
                    'urutan_opsi' => $urut++,
                ]);
            }
        });

        return back()->with('sukses', 'Butir ditambahkan.');
    }

    public function hapusButir(ButirInstrumen $butir)
    {
        $this->pastikanDraft($butir->versi);
        $butir->delete();

        return back()->with('sukses', 'Butir dihapus.');
    }

    public function publikasi(VersiInstrumen $versi)
    {
        abort_if($versi->butir()->count() === 0, 422, 'Versi tanpa butir tidak dapat dikunci.');

        $versi->update(['status_versi' => 'terkunci', 'dikunci_pada' => now()]);

        return back()->with('sukses', 'Versi dikunci dan siap dipakai pelaksanaan instrumen.');
    }

    private function pastikanDraft(VersiInstrumen $versi): void
    {
        abort_if($versi->terkunci(), 422,
            'Versi ini sudah terkunci. Buat versi baru bila butir perlu diubah.');
    }
}
