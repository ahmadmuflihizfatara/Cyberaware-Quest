@extends('layouts.publik')
@section('judul', $program->nama_program)

@section('isi')
<a href="{{ route('program.index') }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Semua program</a>

<div class="mt-3 flex flex-wrap items-center gap-3">
    <h1 class="text-3xl font-bold">{{ $program->nama_program }}</h1>
    <span class="chip {{ $program->status_program === 'berjalan' ? 'chip-ok' : 'chip-off' }}">{{ $program->status_program }}</span>
</div>
<p class="mt-3 max-w-3xl text-slate-600 leading-relaxed">{{ $program->deskripsi }}</p>

<div class="mt-8 grid gap-6 lg:grid-cols-[2fr_1fr]">
    <div>
        <h2 class="text-lg font-bold">Kegiatan dalam program ini</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @forelse ($program->kegiatan as $k)
                @include('publik.partials.kartu-kegiatan', ['k' => $k])
            @empty
                <p class="text-sm text-slate-500">Belum ada kegiatan.</p>
            @endforelse
        </div>
    </div>

    <aside class="card card-pad h-fit">
        <p class="eyebrow">Mitra Terlibat</p>
        <ul class="mt-3 space-y-3">
            @forelse ($program->mitra as $m)
                <li>
                    <p class="font-semibold">{{ $m->nama_mitra }}</p>
                    <p class="text-xs text-slate-500">{{ $m->jenis_mitra }} · {{ $m->pivot->peran_mitra ?? 'mitra pelaksana' }}</p>
                </li>
            @empty
                <li class="text-sm text-slate-500">Belum ada mitra tertaut.</li>
            @endforelse
        </ul>
    </aside>
</div>
@endsection
