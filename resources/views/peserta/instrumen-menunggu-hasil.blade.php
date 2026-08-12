@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', ucfirst($fase))

@section('isi')
<div class="card card-pad max-w-2xl text-center py-12">
    <div class="mx-auto w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
    </div>
    <h2 class="text-xl font-bold mb-2">Menunggu Hasil</h2>
    <p class="text-slate-500 mb-6">
        Anda telah menyelesaikan instrumen {{ ucfirst($fase) }}.<br>
        Silakan tunggu admin atau fasilitator untuk menampilkan hasil nilai Anda.
    </p>
    
    <a href="{{ route('peserta.pendaftaran.show', $p) }}" class="btn btn-outline">Kembali ke Halaman Kegiatan</a>
</div>
@endsection
