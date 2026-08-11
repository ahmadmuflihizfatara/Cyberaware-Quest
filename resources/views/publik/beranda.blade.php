@extends('layouts.publik')
@section('judul', 'Beranda')

@section('isi')
<section class="overflow-hidden rounded-3xl bg-navy-800 text-white">
    <div class="grid gap-8 px-7 py-12 md:px-12 md:py-16 lg:grid-cols-[1.3fr_1fr] lg:items-center">
        <div>
            <p class="eyebrow text-cyan-500">Program Literasi Keamanan Siber</p>
            <h1 class="mt-3 text-3xl font-extrabold leading-tight md:text-[2.65rem]">
                Belajar mengenali ancaman siber, satu sekolah pada satu waktu.
            </h1>
            <p class="mt-4 max-w-xl text-slate-300 leading-relaxed">
                Program literasi keamanan siber untuk siswa sekolah, dijalankan bersama PkM ImpactLab:
                materi, simulasi, dan sertifikasi dalam satu alur terukur — dari pendaftaran,
                pre-test, sesi pembelajaran, gamifikasi, sampai evaluasi penyelenggaraan.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('kegiatan.index') }}" class="btn btn-cyan">Lihat Kegiatan</a>
                <a href="{{ route('verifikasi') }}" class="btn btn-ghost">Verifikasi Sertifikat</a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            @foreach ([
                ['6 Tahap', 'Alur peserta terukur'],
                ['Pre & Post', 'Paket soal berversi sama'],
                ['Gamifikasi', 'Poin terpisah dari skor'],
                ['Sertifikat', 'Nomor & kode verifikasi'],
            ] as [$judul, $ket])
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="font-display text-lg font-bold text-cyan-500">{{ $judul }}</p>
                    <p class="mt-1 text-xs text-slate-300">{{ $ket }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mt-12">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Program Kami</p>
            <h2 class="text-2xl font-bold">Program PkM yang sedang berjalan</h2>
        </div>
        <a href="{{ route('program.index') }}" class="btn btn-ghost btn-sm">Semua program</a>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-3">
        @forelse ($program as $p)
            <a href="{{ route('program.show', $p) }}" class="card card-pad transition hover:border-cyan-500">
                <span class="chip {{ $p->status_program === 'berjalan' ? 'chip-ok' : 'chip-off' }}">{{ $p->status_program }}</span>
                <h3 class="mt-3 text-lg font-bold">{{ $p->nama_program }}</h3>
                <p class="mt-2 line-clamp-3 text-sm text-slate-500">{{ $p->deskripsi }}</p>
            </a>
        @empty
            <p class="text-sm text-slate-500">Belum ada program.</p>
        @endforelse
    </div>
</section>

<section class="mt-12">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Kegiatan Terdekat</p>
            <h2 class="text-2xl font-bold">Jadwal kegiatan terbuka</h2>
        </div>
        <a href="{{ route('kegiatan.index') }}" class="btn btn-ghost btn-sm">Semua kegiatan</a>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-3">
        @forelse ($kegiatan as $k)
            @include('publik.partials.kartu-kegiatan', ['k' => $k])
        @empty
            <p class="text-sm text-slate-500">Belum ada kegiatan terjadwal.</p>
        @endforelse
    </div>
</section>
@endsection
