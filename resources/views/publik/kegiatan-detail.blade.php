@extends('layouts.publik')
@section('judul', $kegiatan->tema)

@section('isi')
<a href="{{ route('kegiatan.index') }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Semua kegiatan</a>

<div class="mt-6 max-w-2xl">
    <div>
        <p class="eyebrow">Detail Kegiatan</p>
        <h1 class="mt-1 text-3xl font-bold leading-tight">{{ $kegiatan->tema }}</h1>
        <p class="mt-2 text-slate-500">
            Program {{ $kegiatan->program?->nama_program }} · {{ $kegiatan->sekolah?->mitra?->nama_mitra }}
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            <span class="chip chip-info">{{ ucfirst($kegiatan->mode_pelaksanaan) }}</span>
            @if ($kegiatan->lokasi)
                <span class="chip">{{ $kegiatan->lokasi->nama_lokasi }}</span>
            @endif
            <span class="chip {{ $kegiatan->status_kegiatan === 'berlangsung' ? 'chip-warn' : 'chip-off' }}">{{ $kegiatan->status_kegiatan }}</span>
        </div>

        <div class="card card-pad mt-6">
            <p class="eyebrow">Jadwal · {{ $kegiatan->tanggal_mulai?->translatedFormat('d F Y') }}</p>
            <ul class="mt-3 divide-y divide-slate-100">
                @forelse ($kegiatan->sesi as $s)
                    <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1 py-3">
                        <span class="font-semibold">Sesi {{ $s->urutan_sesi }} · {{ $s->judul_sesi }}</span>
                        <span class="text-sm text-slate-500">
                            {{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}
                            @if ($s->fasilitator) · {{ $s->fasilitator->nama_fasilitator }} @endif
                        </span>
                        @if ($s->materi->isNotEmpty())
                            <span class="w-full text-xs text-slate-400">Materi: {{ $s->materi->pluck('judul_materi')->join(', ') }}</span>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-500">Jadwal sesi belum dipublikasikan.</li>
                @endforelse
            </ul>
        </div>

        <div class="card card-pad mt-4">
            <p class="eyebrow">Kuota</p>
            @php $terisi = $kegiatan->kapasitas - $kegiatan->sisaKuota(); @endphp
            <p class="mt-1 text-lg font-semibold">{{ $terisi }} dari {{ $kegiatan->kapasitas }} pendaftar</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full bg-cyan-500" style="width: {{ min(100, round($terisi / max(1, $kegiatan->kapasitas) * 100)) }}%"></div>
            </div>
        </div>

        <div class="card card-pad mt-4">
            <p class="eyebrow">Pendaftaran</p>

            @guest
                <p class="mt-3 text-sm text-slate-600">Masuk atau buat akun peserta untuk mendaftar kegiatan ini dari Dashboard Peserta.</p>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('login') }}" class="btn btn-primary flex-1">Masuk</a>
                    <a href="{{ route('registrasi') }}" class="btn btn-ghost flex-1">Daftar Akun</a>
                </div>
            @elseif ($sudahDaftar)
                <p class="mt-3 text-sm text-emerald-700">Anda sudah terdaftar pada kegiatan ini.</p>
                <a href="{{ route('peserta.dashboard') }}" class="btn btn-cyan mt-4 w-full">Buka Dashboard Peserta</a>
            @elseif (! auth()->user()->punyaPeran('peserta'))
                <p class="mt-3 text-sm text-slate-600">Akun ini bukan akun peserta, sehingga tidak dapat mendaftar kegiatan.</p>
            @elseif ($kegiatan->sisaKuota() < 1)
                <p class="mt-3 text-sm text-red-700">Kuota kegiatan sudah penuh.</p>
                <button class="btn btn-primary mt-4 w-full" disabled>Kuota Penuh</button>
            @else
                <p class="mt-3 text-sm text-slate-600">Pendaftaran dilakukan dari Dashboard Peserta.</p>
                <a href="{{ route('peserta.informasi-kegiatan.show', $kegiatan) }}" class="btn btn-cyan mt-4 w-full">Daftar dari Dashboard</a>
            @endif
        </div>
    </div>
</div>
@endsection
