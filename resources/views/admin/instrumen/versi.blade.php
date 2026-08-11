@extends('layouts.app')
@section('judul', 'Versi Instrumen')

@section('isi')
<div class="card card-pad">
    <p class="eyebrow">{{ $i->tipe_instrumen }} · {{ $i->kode_instrumen }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $i->nama_instrumen }}</h2>
    <p class="mt-1 text-sm text-slate-500">{{ $i->deskripsi }}</p>
</div>

<div class="card mt-5 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Versi</th><th>Status</th><th>Butir</th><th>Dikunci</th><th></th></tr></thead>
        <tbody>
        @forelse ($versi as $v)
            <tr>
                <td class="font-semibold">v{{ $v->nomor_versi }}</td>
                <td><span class="chip {{ $v->status_versi === 'draft' ? 'chip-warn' : 'chip-ok' }}">{{ $v->status_versi }}</span></td>
                <td>{{ $v->butir_count }}</td>
                <td>{{ $v->dikunci_pada?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="text-right"><a href="{{ route('admin.instrumen.butir', $v) }}" class="btn btn-ghost btn-sm">Kelola butir</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-sm text-slate-500">Belum ada versi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<form method="POST" action="{{ route('admin.instrumen.versi.simpan', $i) }}" class="mt-4">
    @csrf
    <button class="btn btn-primary">Buat versi baru (draft)</button>
</form>

<a href="{{ route('admin.master.index', 'instrumen') }}" class="btn btn-ghost mt-4">&larr; Kembali ke daftar instrumen</a>
@endsection
