@extends('layouts.app')
@section('judul', 'Hasil Penilaian')

@section('isi')
<form method="GET" class="card card-pad flex flex-wrap items-end gap-3">
    <div class="min-w-64">
        <label class="label">Filter kegiatan</label>
        <select class="select" name="kegiatan">
            <option value="">Semua kegiatan</option>
            @foreach ($kegiatan as $k)
                <option value="{{ $k->id_kegiatan }}" @selected(request('kegiatan') == $k->id_kegiatan)>{{ $k->tema }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    <a href="{{ route('admin.laporan.ekspor', ['hasil-belajar', 'kegiatan' => request('kegiatan')]) }}" class="btn btn-ghost">Ekspor hasil belajar (CSV)</a>
</form>

<div class="card mt-4 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Peserta</th><th>Kegiatan</th><th>Fase</th><th class="text-right">Skor</th><th>Lulus</th><th>Final</th><th></th></tr></thead>
        <tbody>
        @forelse ($baris as $b)
            <tr>
                <td class="font-semibold">{{ $b->nama_peserta }}</td>
                <td>{{ $b->tema }}</td>
                <td><span class="chip chip-info">{{ $b->fase }}</span></td>
                <td class="text-right font-semibold">{{ $b->skor }}</td>
                <td><span class="chip {{ $b->status_lulus ? 'chip-ok' : 'chip-bad' }}">{{ $b->status_lulus ? 'lulus' : 'belum' }}</span></td>
                <td>{{ $b->is_final ? 'ya' : 'tidak' }}</td>
                <td class="text-right">
                    <form method="POST" action="{{ route('admin.penilaian.ulang', $b->id_respons) }}">
                        @csrf
                        <button class="btn btn-ghost btn-sm">Nilai ulang</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-sm text-slate-500">Belum ada hasil penilaian.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $baris->links() }}</div>
@endsection
