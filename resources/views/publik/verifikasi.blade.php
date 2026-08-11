@extends('layouts.publik')
@section('judul', 'Verifikasi Sertifikat')

@section('isi')
<div class="mx-auto max-w-2xl">
    <p class="eyebrow">Publik</p>
    <h1 class="text-3xl font-bold">Verifikasi Sertifikat</h1>
    <p class="mt-2 text-slate-500">
        Masukkan kode verifikasi yang tercetak pada sertifikat untuk memastikan keasliannya.
    </p>

    <form method="GET" class="card card-pad mt-6 flex flex-wrap gap-3">
        <input class="input flex-1" name="kode" value="{{ $kode }}" placeholder="VF-XXXXXX" required>
        <button class="btn btn-primary">Cek</button>
    </form>

    @if ($kode !== '')
        @if ($sertifikat)
            <div class="card card-pad mt-5 border-emerald-200 bg-emerald-50/60">
                <span class="chip {{ $sertifikat->status_sertifikat === 'terbit' ? 'chip-ok' : 'chip-bad' }}">
                    {{ $sertifikat->status_sertifikat }}
                </span>
                <p class="mt-3 font-display text-2xl font-bold">{{ $sertifikat->nomor_sertifikat }}</p>
                <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Atas nama</dt>
                        <dd class="font-semibold">{{ $sertifikat->pendaftaran->peserta->nama_peserta }}</dd></div>
                    <div><dt class="text-slate-500">Kegiatan</dt>
                        <dd class="font-semibold">{{ $sertifikat->pendaftaran->kegiatan->tema }}</dd></div>
                    <div><dt class="text-slate-500">Diterbitkan</dt>
                        <dd class="font-semibold">{{ $sertifikat->diterbitkan_pada->translatedFormat('d F Y') }}</dd></div>
                    <div><dt class="text-slate-500">Kode verifikasi</dt>
                        <dd class="font-semibold">{{ $sertifikat->kode_verifikasi }}</dd></div>
                </dl>
            </div>
        @else
            <div class="card card-pad mt-5 border-red-200 bg-red-50 text-sm text-red-800">
                Kode <strong>{{ $kode }}</strong> tidak ditemukan. Periksa kembali penulisannya.
            </div>
        @endif
    @endif
</div>
@endsection
