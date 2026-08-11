@extends('layouts.app')
@section('judul', 'Dashboard Admin')

@section('isi')
<h2 class="font-display text-2xl font-extrabold">Ringkasan lintas kegiatan</h2>

<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
        ['Total peserta', number_format($totalPeserta, 0, ',', '.')],
        ['Kegiatan aktif', $kegiatanAktif],
        ['Sertifikat terbit', number_format($sertifikatTerbit, 0, ',', '.')],
        ['Poin beredar', number_format($poinBeredar, 0, ',', '.')],
    ] as [$label, $nilai])
        <div class="card card-pad">
            <p class="stat-label">{{ $label }}</p>
            <p class="stat-value mt-1">{{ $nilai }}</p>
        </div>
    @endforeach
</div>

<div class="mt-7 grid gap-5 lg:grid-cols-2 lg:items-start">
    <div>
        <h3 class="text-lg font-bold">Rekap Kegiatan</h3>
        <p class="text-xs text-slate-400">Sumber: view <code>v_rekap_kegiatan</code></p>
        <div class="card mt-3 tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Kegiatan</th><th class="text-right">Pendaftar</th><th class="text-right">Hadir</th></tr></thead>
                <tbody>
                @forelse ($rekap as $r)
                    <tr>
                        <td class="font-semibold">{{ $r->tema }}</td>
                        <td class="text-right">{{ $r->jumlah_pendaftar }}</td>
                        <td class="text-right">{{ $r->jumlah_hadir }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-sm text-slate-500">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-bold">Pendaftaran Terbaru</h3>
        <p class="text-xs text-slate-400">Delapan pendaftaran terakhir</p>
        <div class="card mt-3 tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Nama</th><th>Kegiatan</th><th>Tanggal</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($pendaftaranTerbaru as $p)
                    <tr>
                        <td class="font-semibold">{{ $p->peserta->nama_peserta }}</td>
                        <td>{{ $p->kegiatan->tema }}</td>
                        <td>{{ $p->tanggal_daftar?->translatedFormat('d M Y') }}</td>
                        <td><span class="chip chip-off">{{ $p->status_pendaftaran }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-sm text-slate-500">Belum ada pendaftaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<h3 class="mt-8 text-lg font-bold">Laporan &amp; Dashboard</h3>
<div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
        ['hasil-belajar', 'Hasil Belajar', 'Pre vs post'],
        ['evaluasi', 'Evaluasi', 'Skor per aspek'],
        ['rekap-kegiatan', 'Rekap Kegiatan', 'Kehadiran'],
        ['leaderboard', 'Leaderboard', 'Peringkat poin'],
    ] as [$jenis, $judul, $ket])
        <a href="{{ route('admin.laporan', $jenis) }}" class="card card-pad transition hover:border-cyan-500">
            <p class="font-semibold">{{ $judul }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $ket }}</p>
        </a>
    @endforeach
</div>
@endsection
