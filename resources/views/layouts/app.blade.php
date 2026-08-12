@extends('layouts.base')

@php
    $area = $area ?? (request()->is('admin*') ? 'admin' : (request()->is('fasilitator*') ? 'fasilitator' : 'peserta'));
    $aktifPendaftaran = $aktifPendaftaran ?? null;

    $menu = match ($area) {
        'admin' => [
            'Ringkasan' => [
                ['admin.dashboard', [], 'Dashboard'],
            ],
            'Master Data' => [
                ['admin.master.index', ['pengguna'], 'Pengguna & Peran'],
                ['admin.master.index', ['mitra'], 'Mitra'],
                ['admin.master.index', ['sekolah'], 'Sekolah'],
                ['admin.master.index', ['lokasi'], 'Lokasi'],
                ['admin.master.index', ['fasilitator'], 'Fasilitator'],
                ['admin.master.index', ['materi'], 'Materi'],
                ['admin.master.index', ['badge'], 'Badge'],
                ['admin.master.index', ['reward'], 'Reward'],
            ],
            'Program & Kegiatan' => [
                ['admin.master.index', ['program'], 'Program PkM'],
                ['admin.kegiatan.index', [], 'Kegiatan'],
            ],
            'Instrumen' => [
                ['admin.master.index', ['instrumen'], 'Instrumen & Versi'],
            ],
            'Penilaian & Poin' => [
                ['admin.penilaian', [], 'Hasil Penilaian'],
                ['admin.poin', [], 'Transaksi Poin'],
                ['admin.penukaran', [], 'Penukaran Reward'],
            ],
            'Sertifikat & Laporan' => [
                ['admin.sertifikat', [], 'Sertifikat'],
                ['admin.laporan', ['hasil-belajar'], 'Laporan & Ekspor'],
                ['admin.log', [], 'Log Integrasi'],
            ],
        ],
        'fasilitator' => [
            'Fasilitator' => [
                ['fasilitator.dashboard', [], 'Dashboard'],
                ['fasilitator.kegiatan', [], 'Kegiatan Saya'],
                ['fasilitator.artefak', [], 'Verifikasi Artefak'],
                ['fasilitator.rekap', [], 'Rekap Nilai'],
            ],
        ],
        default => array_filter([
            'Peserta' => [
                ['peserta.dashboard', [], 'Dashboard'],
                ['peserta.informasi-kegiatan', [], 'Informasi Kegiatan'],
                ['peserta.kegiatan', [], 'Kegiatan Saya'],
                ['peserta.poin', [], 'Poin & Mutasi'],
                ['peserta.reward', [], 'Reward'],
                ['peserta.badge', [], 'Badge'],
                ['peserta.profil', [], 'Profil'],
            ],
            'Kegiatan Aktif' => $aktifPendaftaran ? [
                ['peserta.pendaftaran.show', [$aktifPendaftaran], 'Ringkasan Tahapan'],
                ['peserta.instrumen', [$aktifPendaftaran, 'pretest'], 'Pre-test'],
                ['peserta.checkin', [$aktifPendaftaran], 'Check-in Sesi'],
                ['peserta.materi', [$aktifPendaftaran], 'Materi'],
                ['peserta.aktivitas', [$aktifPendaftaran], 'Aktivitas & Artefak'],
                ['peserta.gamifikasi', [$aktifPendaftaran], 'Gamifikasi'],
                ['peserta.instrumen', [$aktifPendaftaran, 'posttest'], 'Post-test'],
                ['peserta.instrumen', [$aktifPendaftaran, 'kuesioner'], 'Kuesioner'],
                ['peserta.sertifikat', [$aktifPendaftaran], 'Sertifikat'],
            ] : null,
        ]),
    };

    $labelArea = ['admin' => 'Area Admin', 'fasilitator' => 'Area Fasilitator', 'peserta' => 'Area Peserta'][$area];
@endphp

@section('body')
<div class="flex min-h-screen">
    <aside class="hidden lg:flex w-64 shrink-0 flex-col bg-navy-800 text-white">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2.5 px-5 py-5">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-500 font-display font-extrabold text-navy-900">C</span>
            <span class="font-display font-extrabold leading-none">
                CyberAware
                <span class="block text-[0.62rem] font-semibold tracking-[0.16em] text-cyan-500 uppercase">{{ $labelArea }}</span>
            </span>
        </a>

        <nav class="flex-1 overflow-y-auto px-3 pb-6 space-y-5">
            @foreach ($menu as $grup => $item)
                <div>
                    <p class="px-3 pb-1.5 text-[0.6rem] font-bold uppercase tracking-[0.14em] text-slate-400">{{ $grup }}</p>
                    <div class="space-y-0.5">
                        @foreach ($item as [$rute, $param, $label])
                            <a href="{{ route($rute, $param) }}"
                               class="sidebar-link {{ url()->current() === route($rute, $param) ? 'aktif' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="sticky top-0 z-10 border-b border-line bg-white/90 backdrop-blur">
            <div class="flex items-center gap-4 px-5 py-3.5">
                <div class="min-w-0">
                    <p class="eyebrow">{{ $labelArea }}</p>
                    <h1 class="truncate text-lg font-bold">@yield('judul', 'Dashboard')</h1>
                </div>
                <div class="ml-auto flex items-center gap-3">
                    <div class="hidden sm:block text-right leading-tight">
                        <p class="text-sm font-semibold">{{ auth()->user()->nama_pengguna }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="btn btn-ghost btn-sm">Keluar</button>
                    </form>
                </div>
            </div>

            <nav class="lg:hidden flex gap-1 overflow-x-auto px-3 pb-2">
                @foreach ($menu as $item)
                    @foreach ($item as [$rute, $param, $label])
                        <a href="{{ route($rute, $param) }}"
                           class="whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ url()->current() === route($rute, $param) ? 'bg-navy-700 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $label }}</a>
                    @endforeach
                @endforeach
            </nav>
        </header>

        <main class="p-5 lg:p-7 max-w-6xl">
            <x-alert />
            @yield('isi')
        </main>
    </div>
</div>
@endsection
