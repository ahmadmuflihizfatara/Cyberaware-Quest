@extends('layouts.app')
@section('judul', 'Penukaran Reward')

@section('isi')
<p class="text-sm text-slate-500">
    Membatalkan penukaran mengembalikan stok reward dan mencatat transaksi poin jenis <code>koreksi</code>.
</p>

<div class="card mt-4 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Waktu</th><th>Peserta</th><th>Reward</th><th>Biaya poin</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($baris as $p)
            <tr>
                <td>{{ $p->waktu_penukaran?->format('d/m/Y H:i') }}</td>
                <td class="font-semibold">{{ $p->pendaftaran->peserta->nama_peserta }}</td>
                <td>{{ $p->reward->nama_reward }}</td>
                <td>{{ $p->biaya_poin_saat_itu }}</td>
                <td><span class="chip {{ ['diproses' => 'chip-warn', 'selesai' => 'chip-ok', 'dibatalkan' => 'chip-bad'][$p->status_penukaran] }}">{{ $p->status_penukaran }}</span></td>
                <td class="text-right">
                    <form method="POST" action="{{ route('admin.penukaran.ubah', $p) }}" class="flex justify-end gap-2">
                        @csrf @method('PUT')
                        <select class="select w-36" name="status_penukaran">
                            @foreach (['diproses', 'selesai', 'dibatalkan'] as $s)
                                <option value="{{ $s }}" @selected($p->status_penukaran === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-ghost btn-sm">Ubah</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-sm text-slate-500">Belum ada penukaran.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $baris->links() }}</div>
@endsection
