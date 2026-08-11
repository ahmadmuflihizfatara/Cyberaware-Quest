@extends('layouts.publik')
@section('judul', 'Program PkM')

@section('isi')
<p class="eyebrow">Area Publik</p>
<h1 class="text-3xl font-bold">Program PkM</h1>
<p class="mt-2 max-w-2xl text-slate-500">Daftar program pengabdian kepada masyarakat beserta kegiatan turunannya.</p>

<div class="mt-7 grid gap-4 md:grid-cols-2">
    @forelse ($program as $p)
        <a href="{{ route('program.show', $p) }}" class="card card-pad transition hover:border-cyan-500">
            <div class="flex items-center justify-between gap-3">
                <span class="chip {{ $p->status_program === 'berjalan' ? 'chip-ok' : 'chip-off' }}">{{ $p->status_program }}</span>
                <span class="text-xs font-semibold text-slate-400">{{ $p->kegiatan_count }} kegiatan</span>
            </div>
            <h2 class="mt-3 text-xl font-bold">{{ $p->nama_program }}</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $p->deskripsi }}</p>
            <p class="mt-3 text-xs text-slate-400">
                {{ $p->tanggal_mulai?->translatedFormat('d M Y') ?? '—' }} s.d.
                {{ $p->tanggal_selesai?->translatedFormat('d M Y') ?? 'sekarang' }}
            </p>
        </a>
    @empty
        <p class="text-sm text-slate-500">Belum ada program.</p>
    @endforelse
</div>
@endsection
