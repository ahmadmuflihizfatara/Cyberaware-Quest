@extends('layouts.app')
@section('judul', 'Dashboard Fasilitator')

@section('isi')
<h2 class="font-display text-2xl font-extrabold">Halo, {{ $f->nama_fasilitator }}</h2>
<p class="mt-1 text-slate-500">{{ $f->bidang_keahlian ?? 'Fasilitator CyberAware Quest' }}</p>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    @foreach ([['Sesi hari ini', $jumlahSesiHariIni], ['Total hadir', $totalHadir], ['Artefak menunggu', $artefakMenunggu->count()]] as [$label, $nilai])
        <div class="card card-pad">
            <p class="stat-label">{{ $label }}</p>
            <p class="stat-value mt-1">{{ $nilai }}</p>
        </div>
    @endforeach
</div>

<h3 class="mt-8 text-lg font-bold">Sesi Anda</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Tanggal</th><th>Jam</th><th>Sesi</th><th>Kegiatan</th><th></th></tr></thead>
        <tbody>
        @forelse ($sesiHariIni as $s)
            <tr>
                <td>{{ $s->tanggal_sesi?->translatedFormat('d M Y') }}</td>
                <td>{{ substr($s->jam_mulai, 0, 5) }}</td>
                <td class="font-semibold">{{ $s->judul_sesi }}</td>
                <td>{{ $s->kegiatan->tema }} · {{ $s->kegiatan->sekolah?->mitra?->nama_mitra }}</td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('fasilitator.sesi.qr', $s) }}" class="btn btn-cyan btn-sm">QR</a>
                    <a href="{{ route('fasilitator.sesi.kehadiran', $s) }}" class="btn btn-ghost btn-sm">Presensi</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-sm text-slate-500">Tidak ada sesi mendatang.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<h3 class="mt-8 text-lg font-bold">Artefak Menunggu Verifikasi</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Peserta</th><th>Judul artefak</th><th>Aktivitas</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($artefakMenunggu as $a)
            <tr>
                <td class="font-semibold">{{ $a->nama_peserta }}</td>
                <td>{{ $a->judul_artefak }}</td>
                <td>{{ $a->judul_aktivitas }}</td>
                <td><span class="chip chip-warn">{{ $a->status_verifikasi }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-sm text-slate-500">Tidak ada artefak menunggu.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<a href="{{ route('fasilitator.artefak') }}" class="btn btn-ghost btn-sm mt-3">Buka halaman verifikasi</a>
@endsection
