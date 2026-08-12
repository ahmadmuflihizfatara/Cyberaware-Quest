@extends('layouts.app')
@section('judul', 'Katalog Reward')

@section('isi')
<div class="card card-pad max-w-xs">
    <p class="stat-label">Saldo poin</p>
    <p class="stat-value mt-1">{{ $saldo }}</p>
</div>

<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($reward as $r)
        @php $cukup = $saldo >= $r->biaya_poin; $adaStok = $r->stok > 0; @endphp
        <div class="card card-pad">
            <h3 class="text-lg font-bold">{{ $r->nama_reward }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $r->biaya_poin }} poin · Stok {{ $r->stok }}</p>

            @if (! $aktif)
                <button class="btn btn-primary btn-sm mt-4 w-full" disabled>Belum terdaftar kegiatan</button>
            @elseif (! $adaStok)
                <button class="btn btn-primary btn-sm mt-4 w-full" disabled>Stok Habis</button>
            @elseif (! $cukup)
                <button class="btn btn-primary btn-sm mt-4 w-full" disabled>Poin Kurang</button>
            @else
                <form method="POST" action="{{ route('peserta.reward.tukar', $r) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="id_pendaftaran" value="{{ $aktif->id_pendaftaran }}">
                    <button class="btn btn-cyan btn-sm w-full">Tukar</button>
                </form>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada reward aktif.</p>
    @endforelse
</div>

<h3 class="mt-8 text-lg font-bold">Riwayat Penukaran</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Waktu</th><th>Reward</th><th>Biaya poin</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($riwayat as $h)
            <tr>
                <td>{{ $h->waktu_penukaran?->translatedFormat('d M Y H:i') }}</td>
                <td class="font-semibold">{{ $h->reward->nama_reward }}</td>
                <td>{{ $h->biaya_poin_saat_itu }}</td>
                <td><span class="chip {{ ['diproses' => 'chip-warn', 'selesai' => 'chip-ok', 'dibatalkan' => 'chip-bad'][$h->status_penukaran] }}">{{ $h->status_penukaran }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-sm text-slate-500">Belum ada penukaran.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
