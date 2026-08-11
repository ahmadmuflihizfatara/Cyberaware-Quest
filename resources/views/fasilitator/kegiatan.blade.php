@extends('layouts.app')
@section('judul', 'Kegiatan Saya')

@section('isi')
<div class="grid gap-4 md:grid-cols-2">
    @forelse ($kegiatan as $k)
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip chip-info">{{ $k->mode_pelaksanaan }}</span>
                <span class="chip {{ $k->status_kegiatan === 'berlangsung' ? 'chip-warn' : 'chip-off' }}">{{ $k->status_kegiatan }}</span>
                <span class="chip">{{ $k->pivot->peran_penugasan }}</span>
            </div>
            <h3 class="mt-3 text-lg font-bold">{{ $k->tema }}</h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ $k->sekolah?->mitra?->nama_mitra }} · {{ $k->tanggal_mulai?->translatedFormat('d M Y') }}
            </p>
            <a href="{{ route('fasilitator.kegiatan.show', $k) }}" class="btn btn-primary btn-sm mt-4">Kelola sesi</a>
        </div>
    @empty
        <p class="text-sm text-slate-500">Anda belum ditugaskan pada kegiatan mana pun.</p>
    @endforelse
</div>
@endsection
