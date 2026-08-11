@extends('layouts.app')
@section('judul', 'Verifikasi Artefak')

@section('isi')
<div class="space-y-4">
    @forelse ($artefak as $a)
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip {{ ['menunggu' => 'chip-warn', 'terverifikasi' => 'chip-ok', 'ditolak' => 'chip-bad'][$a->status_verifikasi] }}">
                    {{ $a->status_verifikasi }}
                </span>
                @if ($a->tool_ai)<span class="chip">{{ $a->tool_ai }}</span>@endif
                <span class="text-xs text-slate-400">{{ $a->judul_sesi }} · {{ $a->judul_aktivitas }}</span>
            </div>
            <h3 class="mt-2 text-lg font-bold">{{ $a->judul_artefak }}</h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ $a->nama_peserta }} · {{ $a->tipe_file }}
                @if ($a->ukuran_file_kb) · {{ $a->ukuran_file_kb }} KB @endif
                · diunggah {{ \Illuminate\Support\Carbon::parse($a->diunggah_pada)->translatedFormat('d M Y H:i') }}
            </p>
            <a href="{{ $a->tautan_atau_file }}" target="_blank" rel="noopener"
               class="mt-2 inline-block text-sm font-semibold text-navy-700 hover:underline">Buka artefak &rarr;</a>

            <form method="POST" action="{{ route('fasilitator.artefak.verifikasi', $a->id_artefak) }}"
                  class="mt-4 grid gap-3 sm:grid-cols-[auto_1fr_auto]">
                @csrf
                <div>
                    <label class="label">Keputusan</label>
                    <select class="select" name="status_verifikasi" required>
                        <option value="terverifikasi">terverifikasi</option>
                        <option value="ditolak">ditolak</option>
                    </select>
                </div>
                <div>
                    <label class="label">Catatan revisi</label>
                    <input class="input" name="catatan_revisi" value="{{ $a->catatan_revisi }}">
                </div>
                <div class="flex items-end"><button class="btn btn-primary w-full">Simpan</button></div>
            </form>
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada artefak pada sesi yang Anda bawakan.</p>
    @endforelse
</div>
@endsection
