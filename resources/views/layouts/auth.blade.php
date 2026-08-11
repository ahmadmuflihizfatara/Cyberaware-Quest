@extends('layouts.base')

@section('body')
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="hidden flex-col justify-between bg-navy-800 p-12 text-white lg:flex">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-500 font-display font-extrabold text-navy-900">C</span>
            <span class="font-display font-extrabold leading-none">CyberAware Quest
                <span class="block text-[0.62rem] font-semibold tracking-[0.16em] text-cyan-500 uppercase">PkM ImpactLab</span>
            </span>
        </a>

        <div>
            <h2 class="font-display text-3xl font-extrabold leading-tight">
                Satu akun, tiga peran:<br>peserta, fasilitator, admin.
            </h2>
            <p class="mt-4 max-w-md text-slate-300 leading-relaxed">
                Akses fitur mengikuti peran yang diberikan panitia. Peserta mengikuti alur enam tahap,
                fasilitator mengelola sesi dan presensi, admin memegang data master dan laporan.
            </p>
        </div>

        <p class="text-xs text-slate-400">Poltek SSN · Proyek UAS Terpadu Basis Data &amp; Pemrograman Web</p>
    </div>

    <div class="flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <x-alert />
            @yield('isi')
        </div>
    </div>
</div>
@endsection
