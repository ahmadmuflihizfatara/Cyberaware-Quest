@extends('layouts.app')
@section('judul', 'Laporan & Ekspor')

@section('isi')
<div class="flex flex-wrap gap-2">
    @foreach ($daftar as $kunci => $label)
        <a href="{{ route('admin.laporan', [$kunci, 'kegiatan' => $terpilih]) }}"
           class="btn btn-sm {{ $jenis === $kunci ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card card-pad mt-5">
    <h2 class="font-display text-xl font-extrabold">{{ $def['judul'] }}</h2>

    <form method="GET" class="mt-4 flex flex-wrap items-end gap-3">
        <div class="min-w-64">
            <label class="label">Filter kegiatan</label>
            <select class="select" name="kegiatan">
                <option value="">Semua kegiatan</option>
                @foreach ($kegiatan as $k)
                    <option value="{{ $k->id_kegiatan }}" @selected($terpilih == $k->id_kegiatan)>{{ $k->tema }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary">Terapkan filter</button>
        <a href="{{ route('admin.laporan.ekspor', [$jenis, 'kegiatan' => $terpilih]) }}" class="btn btn-cyan">Unduh CSV</a>
    </form>

    <p class="mt-3 text-xs text-slate-400">
        Data diambil langsung dari query aplikasi mengikuti filter yang dipilih — bukan berkas statis.
    </p>
</div>

<div class="card mt-4 tbl-wrap">
    <table class="tbl">
        <thead>
            <tr>@foreach ($def['kolom'] as $label)<th>{{ $label }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse ($baris as $b)
            <tr>
                @foreach ($def['kolom'] as $kolom => $label)
                    <td>{{ $b->{$kolom} ?? '—' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($def['kolom']) }}" class="text-sm text-slate-500">Belum ada data untuk filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<p class="mt-3 text-xs text-slate-400">Menampilkan maksimal 500 baris; unduhan CSV memuat seluruh baris.</p>
@endsection
