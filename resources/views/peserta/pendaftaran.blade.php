@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', $p->kegiatan->tema)

@section('isi')
<div class="card card-pad">
    <p class="eyebrow">Pendaftaran #{{ $p->id_pendaftaran }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $p->kegiatan->tema }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        {{ $p->kegiatan->sekolah?->mitra?->nama_mitra }} ·
        {{ $p->kegiatan->tanggal_mulai?->translatedFormat('d F Y') }} ·
        {{ ucfirst($p->kegiatan->mode_pelaksanaan) }}
        @if ($p->afiliasi) · Afiliasi: {{ $p->afiliasi->mitra->nama_mitra }} ({{ $p->afiliasi->peran_afiliasi }}) @endif
    </p>
</div>

<h3 class="mt-7 text-lg font-bold">Alur enam tahap</h3>
@include('peserta.partials.tahapan', ['p' => $p, 'tahapan' => $tahapan])

@php $hadir = \App\Models\Kehadiran::where('id_pendaftaran', $p->id_pendaftaran)->exists(); @endphp
<div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
        ['peserta.materi', 'Materi Sesi', 'Slide dan bahan bacaan tiap sesi'],
        ['peserta.aktivitas', 'Aktivitas & Artefak', 'Unggah hasil karya tool AI'],
        ['peserta.gamifikasi', 'Gamifikasi', 'Kuis praktik, tantangan, game'],
        ['peserta.leaderboard', 'Leaderboard', 'Peringkat poin diperoleh'],
    ] as [$rute, $judul, $ket])
        @if($hadir)
            <a href="{{ route($rute, $p) }}" class="card card-pad transition hover:border-cyan-500">
                <p class="font-semibold">{{ $judul }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $ket }}</p>
            </a>
        @else
            <div class="card card-pad opacity-60 cursor-not-allowed" title="Lakukan Check-in terlebih dahulu untuk membuka menu ini.">
                <p class="font-semibold flex items-center justify-between">
                    {{ $judul }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ $ket }}</p>
            </div>
        @endif
    @endforeach
</div>

<h3 class="mt-8 text-lg font-bold">Jadwal sesi</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>#</th><th>Sesi</th><th>Tanggal</th><th>Jam</th><th>Materi</th></tr></thead>
        <tbody>
        @forelse ($p->kegiatan->sesi as $s)
            <tr>
                <td>{{ $s->urutan_sesi }}</td>
                <td class="font-semibold">{{ $s->judul_sesi }}</td>
                <td>{{ $s->tanggal_sesi?->translatedFormat('d M Y') }}</td>
                <td>{{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}</td>
                <td class="text-slate-500">{{ $s->materi->pluck('judul_materi')->join(', ') ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-sm text-slate-500">Belum ada sesi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
