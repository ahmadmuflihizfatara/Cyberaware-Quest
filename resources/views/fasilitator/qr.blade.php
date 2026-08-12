@extends('layouts.app')
@section('judul', 'Token QR Sesi')

@section('isi')
<a href="{{ route('fasilitator.kegiatan.show', $s->kegiatan) }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Kembali ke kegiatan</a>

<div class="animasi-masuk mt-4 grid gap-5 lg:grid-cols-[1fr_1fr] lg:items-start">
    <div class="card card-pad" x-data="{ submitting: false }">
        <p class="eyebrow">Sesi {{ $s->urutan_sesi }}</p>
        <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $s->judul_sesi }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ $s->kegiatan->tema }} · {{ $s->tanggal_sesi?->translatedFormat('d M Y') }} ·
            {{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}
        </p>

        <form method="POST" action="{{ route('fasilitator.sesi.qr.buat', $s) }}" class="mt-5 flex flex-wrap items-end gap-3"
              @submit="submitting = true">
            @csrf
            <div>
                <label class="label" for="menit">Masa berlaku (menit)</label>
                <input class="input w-32" id="menit" type="number" name="menit" value="15" min="1" max="120">
            </div>
            <button class="btn btn-cyan" :disabled="submitting">
                <span x-show="!submitting">Buat / perbarui token</span>
                <span x-show="submitting" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Membuat&hellip;
                </span>
            </button>
        </form>

        <p class="mt-3 text-xs text-slate-400">
            Token divalidasi di server: kecocokan sesi, masa berlaku, dan duplikasi kehadiran.
        </p>
        <a href="{{ route('fasilitator.sesi.kehadiran', $s) }}" class="btn btn-ghost btn-sm mt-3">Lihat presensi sesi</a>
    </div>

    <div class="card card-pad text-center">
        @if ($token && $token->masihBerlaku())
            <div x-data="{
                    token: @js($token->token),
                    kadaluarsa: @js($token->berlaku_hingga->toIso8601String()),
                    sisaDetik: 0,
                    disalin: false,
                    tick() {
                        this.sisaDetik = Math.max(0, Math.round((new Date(this.kadaluarsa) - new Date()) / 1000));
                    },
                    get formatSisa() {
                        const m = String(Math.floor(this.sisaDetik / 60)).padStart(2, '0');
                        const d = String(this.sisaDetik % 60).padStart(2, '0');
                        return `${m}:${d}`;
                    },
                    get hampirHabis() { return this.sisaDetik > 0 && this.sisaDetik <= 120; },
                    get habis() { return this.sisaDetik <= 0; },
                    salin() {
                        navigator.clipboard.writeText(this.token);
                        this.disalin = true;
                        setTimeout(() => this.disalin = false, 1500);
                    },
                 }"
                 x-init="tick(); setInterval(() => tick(), 1000)">
                <template x-if="!habis">
                    <div>
                        <p class="eyebrow">Token aktif</p>
                        <canvas class="mx-auto mt-4" data-qr="{{ $token->token }}"></canvas>

                        <button type="button" @click="salin()"
                                class="mx-auto mt-4 flex items-center gap-2 font-mono text-2xl font-bold tracking-[0.2em] text-navy-800 hover:text-cyan-600">
                            <span x-text="token"></span>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <p class="mt-1 text-xs" :class="disalin ? 'text-emerald-600' : 'text-transparent'" x-text="disalin ? 'Token disalin.' : '—'"></p>

                        <p class="mt-2 text-sm font-semibold transition-colors" :class="hampirHabis ? 'text-amber-600' : 'text-slate-500'">
                            Berlaku <span x-text="formatSisa"></span> lagi
                        </p>
                        <p class="text-xs text-slate-400">
                            hingga {{ $token->berlaku_hingga->translatedFormat('H:i:s, d M Y') }}
                        </p>
                    </div>
                </template>
                <template x-if="habis">
                    <div class="py-10">
                        <p class="text-sm font-semibold text-red-600">Token sudah kedaluwarsa.</p>
                        <p class="mt-1 text-sm text-slate-500">Buat token baru untuk membuka check-in lagi.</p>
                    </div>
                </template>
            </div>
        @else
            <div class="py-10">
                <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <p class="mt-3 text-sm text-slate-500">Belum ada token aktif. Buat token untuk membuka check-in.</p>
            </div>
        @endif
    </div>
</div>
@endsection
