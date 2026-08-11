@extends('layouts.app')
@section('judul', 'Token QR Sesi')

@section('isi')
<div class="grid gap-5 lg:grid-cols-[1fr_1fr] lg:items-start">
    <div class="card card-pad">
        <p class="eyebrow">Sesi {{ $s->urutan_sesi }}</p>
        <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $s->judul_sesi }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ $s->kegiatan->tema }} · {{ $s->tanggal_sesi?->translatedFormat('d M Y') }} ·
            {{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}
        </p>

        <form method="POST" action="{{ route('fasilitator.sesi.qr.buat', $s) }}" class="mt-5 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="label" for="menit">Masa berlaku (menit)</label>
                <input class="input w-32" id="menit" type="number" name="menit" value="15" min="1" max="120">
            </div>
            <button class="btn btn-cyan">Buat / perbarui token</button>
        </form>

        <p class="mt-3 text-xs text-slate-400">
            Token divalidasi di server: kecocokan sesi, masa berlaku, dan duplikasi kehadiran.
        </p>
        <a href="{{ route('fasilitator.sesi.kehadiran', $s) }}" class="btn btn-ghost btn-sm mt-3">Lihat presensi sesi</a>
    </div>

    <div class="card card-pad text-center">
        @if ($token && $token->masihBerlaku())
            <p class="eyebrow">Token aktif</p>
            <canvas class="mx-auto mt-4" data-qr="{{ $token->token }}"></canvas>
            <p class="mt-4 font-mono text-2xl font-bold tracking-[0.2em]">{{ $token->token }}</p>
            <p class="mt-2 text-sm text-slate-500">
                Berlaku hingga {{ $token->berlaku_hingga->translatedFormat('H:i:s, d M Y') }}
            </p>
        @else
            <p class="py-10 text-sm text-slate-500">Belum ada token aktif. Buat token untuk membuka check-in.</p>
        @endif
    </div>
</div>
@endsection
