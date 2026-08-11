@extends('layouts.app', ['aktifPendaftaran' => $pendaftaran->first()])
@section('judul', 'Poin & Mutasi')

@section('isi')
<div class="card card-pad max-w-xs">
    <p class="stat-label">Saldo saat ini</p>
    <p class="stat-value mt-1">{{ $saldo }}</p>
    <p class="mt-1 text-xs text-slate-400">Sumber: view <code>v_saldo_poin</code> (jumlah histori transaksi).</p>
</div>

<div class="card mt-5 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Keterangan</th><th class="text-right">Jumlah</th></tr></thead>
        <tbody>
        @forelse ($mutasi as $m)
            <tr>
                <td>{{ $m->dibuat_pada?->translatedFormat('d M Y H:i') }}</td>
                <td><span class="chip {{ $m->jenis_transaksi === 'perolehan' ? 'chip-ok' : ($m->jenis_transaksi === 'penukaran' ? 'chip-warn' : 'chip-info') }}">{{ $m->jenis_transaksi }}</span></td>
                <td>{{ $m->keterangan }}</td>
                <td class="text-right font-semibold {{ $m->jumlah_poin > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $m->jumlah_poin > 0 ? '+' : '' }}{{ $m->jumlah_poin }}
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-sm text-slate-500">Belum ada mutasi poin.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
