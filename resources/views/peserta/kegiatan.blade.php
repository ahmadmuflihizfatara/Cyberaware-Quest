@extends('layouts.app', ['aktifPendaftaran' => $pendaftaran->first()])
@section('judul', 'Kegiatan Saya')

@section('isi')
<div class="grid gap-4 md:grid-cols-2">
    @forelse ($pendaftaran as $p)
        <div class="card card-pad">
            <div class="flex items-center gap-2">
                <span class="chip {{ $p->status_pendaftaran === 'hadir' ? 'chip-ok' : 'chip-off' }}">{{ $p->status_pendaftaran }}</span>
                <span class="chip chip-info">{{ $p->kegiatan->mode_pelaksanaan }}</span>
            </div>
            <h3 class="mt-3 text-lg font-bold">{{ $p->kegiatan->tema }}</h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ $p->kegiatan->sekolah?->mitra?->nama_mitra }} · {{ $p->kegiatan->tanggal_mulai?->translatedFormat('d M Y') }}
            </p>
            <p class="mt-2 text-xs text-slate-400">Terdaftar {{ $p->tanggal_daftar?->translatedFormat('d M Y H:i') }}</p>
            <a href="{{ route('peserta.pendaftaran.show', $p) }}" class="btn btn-primary btn-sm mt-4">Buka tahapan</a>
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada pendaftaran.
            <a class="font-semibold text-navy-700 hover:underline" href="{{ route('kegiatan.index') }}">Cari kegiatan &rarr;</a></p>
    @endforelse
</div>
@endsection
