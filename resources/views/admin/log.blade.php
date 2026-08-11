@extends('layouts.app')
@section('judul', 'Log Integrasi')

@section('isi')
<p class="text-sm text-slate-500">Jejak audit lintas modul (tabel <code>log_integrasi</code>).</p>

<div class="card mt-4 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Waktu</th><th>Modul</th><th>Kejadian</th><th>Keterangan</th><th>Oleh</th></tr></thead>
        <tbody>
        @forelse ($baris as $l)
            <tr>
                <td>{{ $l->dibuat_pada?->format('d/m/Y H:i:s') }}</td>
                <td><span class="chip chip-info">{{ $l->nama_modul }}</span></td>
                <td class="font-semibold">{{ $l->jenis_kejadian }}</td>
                <td class="text-slate-500">{{ $l->keterangan }}</td>
                <td>{{ $l->pengguna?->email ?? 'sistem' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-sm text-slate-500">Belum ada log.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $baris->links() }}</div>
@endsection
