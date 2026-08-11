@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Sertifikat')

@section('isi')
@if (! $p->sertifikat)
    <div class="card card-pad max-w-2xl">
        <p class="eyebrow">Tahap 6</p>
        <h2 class="mt-1 font-display text-2xl font-extrabold">Sertifikat belum terbit</h2>
        <p class="mt-2 text-sm text-slate-600">
            Syarat penerbitan: minimal satu kehadiran sesi, pre-test dan post-test final,
            serta kuesioner penyelenggaraan sudah dikirim.
        </p>
        <a href="{{ route('peserta.pendaftaran.show', $p) }}" class="btn btn-primary mt-4">Lihat tahapan yang tersisa</a>
    </div>
@else
    @php $s = $p->sertifikat; @endphp
    <div class="sertifikat mx-auto max-w-3xl rounded-2xl border-[6px] border-navy-800 bg-white p-10 text-center shadow">
        <p class="eyebrow">Sertifikat Penyelesaian</p>
        <p class="mt-6 text-sm text-slate-500">Diberikan kepada</p>
        <h2 class="mt-1 font-display text-3xl font-extrabold">{{ $p->peserta->nama_peserta }}</h2>
        <p class="mt-4 text-sm text-slate-500">telah menyelesaikan kegiatan</p>
        <p class="mt-1 font-display text-xl font-bold">{{ $p->kegiatan->tema }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $p->kegiatan->sekolah?->mitra?->nama_mitra }}</p>

        <div class="mt-8 flex flex-wrap justify-center gap-x-8 gap-y-2 text-xs text-slate-500">
            <span>No. <strong class="text-slate-700">{{ $s->nomor_sertifikat }}</strong></span>
            <span>{{ $s->diterbitkan_pada->translatedFormat('d F Y') }}</span>
            <span>Kode: <strong class="text-slate-700">{{ $s->kode_verifikasi }}</strong></span>
        </div>

        @if ($s->status_sertifikat === 'dicabut')
            <p class="mt-6 font-semibold text-red-600">Sertifikat ini telah dicabut.</p>
        @endif
    </div>

    <div class="no-print mt-5 flex flex-wrap justify-center gap-3">
        <button onclick="window.print()" class="btn btn-cyan">Unduh PDF (cetak)</button>
        <a href="{{ route('verifikasi', ['kode' => $s->kode_verifikasi]) }}" class="btn btn-ghost">Halaman verifikasi publik</a>
    </div>
@endif
@endsection
