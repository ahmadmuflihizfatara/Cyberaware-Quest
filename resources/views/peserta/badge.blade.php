@extends('layouts.app')
@section('judul', 'Koleksi Badge')

@section('isi')
<p class="text-sm text-slate-500">{{ $dimiliki->count() }} dari {{ $badge->count() }} badge diperoleh.</p>

<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($badge as $b)
        @php $punya = $dimiliki->contains($b->id_badge); @endphp
        <div class="card card-pad {{ $punya ? '' : 'opacity-60' }}">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl font-display text-lg font-extrabold
                             {{ $punya ? 'bg-cyan-500 text-navy-900' : 'bg-slate-100 text-slate-400' }}">
                    {{ mb_substr($b->nama_badge, 0, 1) }}
                </span>
                <div>
                    <p class="font-bold leading-tight">{{ $b->nama_badge }}</p>
                    <span class="chip {{ $punya ? 'chip-ok' : 'chip-off' }} mt-1">{{ $punya ? 'Diperoleh' : 'Belum' }}</span>
                </div>
            </div>
            <p class="mt-3 text-sm text-slate-500">{{ $b->deskripsi }}</p>
            @if ($b->kriteria)
                <p class="mt-2 text-xs text-slate-400">Kriteria: {{ $b->kriteria }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada badge terdefinisi.</p>
    @endforelse
</div>
@endsection
