@extends('layouts.app')
@section('judul', $k->tema)

@section('isi')
<div class="card card-pad">
    <p class="eyebrow">Kegiatan</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $k->tema }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        {{ $k->sekolah?->mitra?->nama_mitra }}
        @if ($k->lokasi) · {{ $k->lokasi->nama_lokasi }} @endif
        · {{ ucfirst($k->mode_pelaksanaan) }} · kapasitas {{ $k->kapasitas }}
    </p>
</div>

<h3 class="mt-7 text-lg font-bold">Sesi</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>#</th><th>Sesi</th><th>Tanggal</th><th>Jam</th><th>Materi</th><th></th></tr></thead>
        <tbody>
        @forelse ($sesi as $s)
            @php $saya = $milikSaya->contains($s->id_sesi); @endphp
            <tr>
                <td>{{ $s->urutan_sesi }}</td>
                <td class="font-semibold">{{ $s->judul_sesi }}</td>
                <td>{{ $s->tanggal_sesi?->translatedFormat('d M Y') }}</td>
                <td>{{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}</td>
                <td class="text-slate-500">{{ $s->materi->pluck('judul_materi')->join(', ') ?: '—' }}</td>
                <td class="text-right whitespace-nowrap">
                    @if ($saya)
                        <a href="{{ route('fasilitator.sesi.qr', $s) }}" class="btn btn-cyan btn-sm">QR</a>
                        <a href="{{ route('fasilitator.sesi.kehadiran', $s) }}" class="btn btn-ghost btn-sm">Presensi</a>
                        <a href="{{ route('fasilitator.sesi.aktivitas', $s) }}" class="btn btn-ghost btn-sm">Aktivitas</a>
                    @else
                        <span class="chip chip-off">fasilitator lain</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-sm text-slate-500">Belum ada sesi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
