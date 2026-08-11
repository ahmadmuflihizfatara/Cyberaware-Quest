@extends('layouts.base')

@section('body')
<header class="bg-navy-800 text-white">
    <div class="mx-auto max-w-6xl px-5 py-4 flex items-center gap-6">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-500 font-display font-extrabold text-navy-900">C</span>
            <span class="font-display font-extrabold tracking-tight leading-none">
                CyberAware Quest
                <span class="block text-[0.62rem] font-semibold tracking-[0.16em] text-cyan-500 uppercase">PkM ImpactLab</span>
            </span>
        </a>

        <nav class="ml-auto hidden md:flex items-center gap-1 text-sm">
            @foreach ([
                'beranda' => 'Beranda',
                'program.index' => 'Program',
                'kegiatan.index' => 'Kegiatan',
                'verifikasi' => 'Verifikasi Sertifikat',
            ] as $rute => $label)
                <a href="{{ route($rute) }}"
                   class="rounded-lg px-3 py-2 font-medium {{ request()->routeIs($rute) ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="ml-auto md:ml-0 flex items-center gap-2">
            @auth
                <a href="{{ \App\Http\Controllers\AuthController::berandaPeran(auth()->user()->peranUtama()) }}"
                   class="btn btn-cyan btn-sm">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn btn-ghost btn-sm">Keluar</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Masuk</a>
                <a href="{{ route('registrasi') }}" class="btn btn-cyan btn-sm">Daftar</a>
            @endauth
        </div>
    </div>
</header>

<main class="mx-auto max-w-6xl px-5 py-10">
    <x-alert />
    @yield('isi')
</main>

<footer class="mt-16 border-t border-line bg-white">
    <div class="mx-auto max-w-6xl px-5 py-8 text-sm text-slate-500 flex flex-wrap gap-3 justify-between">
        <p>CyberAware Quest × PkM ImpactLab — Poltek SSN.</p>
        <p>Proyek UAS Terpadu Basis Data &amp; Pemrograman Web.</p>
    </div>
</footer>
@endsection
