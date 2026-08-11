@extends('layouts.app')
@section('judul', 'Transaksi Poin')

@section('isi')
<form method="POST" action="{{ route('admin.poin.koreksi') }}" class="card card-pad grid gap-3 sm:grid-cols-[2fr_1fr_2fr_auto]">
    @csrf
    <div>
        <label class="label">Pendaftaran</label>
        <select class="select" name="id_pendaftaran" required>
            @foreach ($pendaftaran as $p)
                <option value="{{ $p->id_pendaftaran }}">#{{ $p->id_pendaftaran }} · {{ $p->peserta->nama_peserta }} — {{ $p->kegiatan->tema }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Jumlah poin</label>
        <input class="input" type="number" name="jumlah_poin" required placeholder="mis. -50">
    </div>
    <div>
        <label class="label">Alasan koreksi</label>
        <input class="input" name="keterangan" required>
    </div>
    <div class="flex items-end"><button class="btn btn-primary w-full">Catat koreksi</button></div>
</form>
<p class="mt-2 text-xs text-slate-400">Koreksi manual wajib beralasan; nilai 0 ditolak oleh CHECK di basis data.</p>

<div class="card mt-5 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Waktu</th><th>Peserta</th><th>Kegiatan</th><th>Jenis</th><th>Keterangan</th><th class="text-right">Jumlah</th></tr></thead>
        <tbody>
        @forelse ($baris as $t)
            <tr>
                <td>{{ $t->dibuat_pada?->format('d/m/Y H:i') }}</td>
                <td class="font-semibold">{{ $t->pendaftaran->peserta->nama_peserta }}</td>
                <td>{{ $t->pendaftaran->kegiatan->tema }}</td>
                <td><span class="chip {{ $t->jenis_transaksi === 'perolehan' ? 'chip-ok' : ($t->jenis_transaksi === 'penukaran' ? 'chip-warn' : 'chip-info') }}">{{ $t->jenis_transaksi }}</span></td>
                <td class="text-slate-500">{{ $t->keterangan }}</td>
                <td class="text-right font-semibold {{ $t->jumlah_poin > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $t->jumlah_poin > 0 ? '+' : '' }}{{ $t->jumlah_poin }}
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-sm text-slate-500">Belum ada transaksi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $baris->links() }}</div>
@endsection
