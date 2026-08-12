@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', ucfirst($fase))

@section('isi')
<a href="{{ route('peserta.pendaftaran.show', $p) }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Kembali ke tahapan</a>

<div class="animasi-masuk card card-pad mt-4 max-w-2xl py-12 text-center">
    <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-amber-100 text-amber-600">
        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
        </svg>
    </span>
    <h2 class="mt-4 font-display text-2xl font-extrabold">Menunggu Hasil</h2>
    <p class="mt-2 text-sm leading-relaxed text-slate-500">
        Jawaban {{ ucfirst($fase) }} Anda sudah tersimpan sebagai respons final.<br>
        Panitia akan menampilkan nilainya begitu proses verifikasi selesai.
    </p>
    <a href="{{ route('peserta.pendaftaran.show', $p) }}" class="btn btn-ghost mt-6">Kembali ke halaman kegiatan</a>
</div>
@endsection
