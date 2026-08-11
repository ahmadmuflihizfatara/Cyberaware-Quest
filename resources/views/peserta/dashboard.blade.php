@extends('layouts.app', ['aktifPendaftaran' => $aktif])
@section('judul', 'Dashboard Peserta')

@section('isi')
<h2 class="font-display text-2xl font-extrabold">Halo, {{ $peserta?->nama_peserta ?? auth()->user()->nama_pengguna }}</h2>

@if (! $aktif)
    <div class="card card-pad mt-5">
        <p class="text-sm text-slate-600">
            Anda belum terdaftar pada kegiatan apa pun.
            <a class="font-semibold text-navy-700 hover:underline" href="{{ route('kegiatan.index') }}">Lihat kegiatan terbuka &rarr;</a>
        </p>
    </div>
@else
    <p class="mt-1 text-slate-500">
        {{ $aktif->kegiatan->tema }} · {{ $aktif->kegiatan->sekolah?->mitra?->nama_mitra }}
    </p>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([['Saldo Poin', $saldo], ['Badge', $badgeDidapat.' / '.$badgeTotal], ['Peringkat', $peringkat ? '#'.$peringkat : '—']] as [$label, $nilai])
            <div class="card card-pad">
                <p class="stat-label">{{ $label }}</p>
                <p class="stat-value mt-1">{{ $nilai }}</p>
            </div>
        @endforeach
    </div>

    <h3 class="mt-8 text-lg font-bold">Progres enam tahap</h3>
    @include('peserta.partials.tahapan', ['p' => $aktif, 'tahapan' => $tahapan])
@endif

<h3 class="mt-9 text-lg font-bold">Kegiatan Saya</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Kegiatan</th><th>Sekolah</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($pendaftaran as $p)
            <tr>
                <td class="font-semibold">{{ $p->kegiatan->tema }}</td>
                <td>{{ $p->kegiatan->sekolah?->mitra?->nama_mitra }}</td>
                <td>{{ $p->kegiatan->tanggal_mulai?->translatedFormat('d M Y') }}</td>
                <td><span class="chip {{ $p->status_pendaftaran === 'hadir' ? 'chip-ok' : 'chip-off' }}">{{ $p->status_pendaftaran }}</span></td>
                <td class="text-right"><a href="{{ route('peserta.pendaftaran.show', $p) }}" class="btn btn-ghost btn-sm">Buka</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-sm text-slate-500">Belum ada pendaftaran.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
