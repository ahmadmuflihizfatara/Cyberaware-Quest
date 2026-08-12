@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Check-in Sesi')

@section('isi')
<a href="{{ route('peserta.pendaftaran.show', $p) }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Kembali ke tahapan</a>

<div class="animasi-masuk mt-4 grid gap-5 lg:grid-cols-[1fr_1fr] lg:items-start">
    <div class="card card-pad" x-data="{ submitting: false }">
        <p class="eyebrow">Tahap 3 · Check-in</p>
        <h2 class="mt-1 font-display text-2xl font-extrabold">Pindai QR sesi</h2>
        <p class="mt-1 text-sm text-slate-500">
            Token dibuka fasilitator dan berlaku terbatas. Bila kamera tidak tersedia, ketik token manual
            atau tempel screenshot QR (<kbd class="rounded border border-line bg-slate-50 px-1 py-0.5 font-mono text-xs">Ctrl</kbd>+<kbd class="rounded border border-line bg-slate-50 px-1 py-0.5 font-mono text-xs">V</kbd>).
        </p>

        <div class="mt-4 overflow-hidden rounded-xl">
            <video id="pratinjau-kamera" class="hidden w-full bg-slate-900" playsinline muted></video>
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span id="status-dot" class="h-2 w-2 shrink-0 rounded-full bg-slate-300 transition-colors"></span>
            <p id="status-pindai" class="text-sm text-slate-500">Menunggu pindaian token QR sesi.</p>
        </div>

        <form method="POST" action="{{ route('peserta.checkin.simpan', $p) }}" class="mt-4 space-y-3" @submit="submitting = true">
            @csrf
            <div>
                <label class="label" for="token">Token sesi</label>
                <input class="input font-mono uppercase" id="token" name="token" value="{{ $token }}"
                       oninput="this.value = this.value.toUpperCase()" required>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" id="mulai-pindai" class="btn btn-ghost">Pindai QR</button>
                <button type="button" id="batal-pindai" class="btn btn-ghost hidden">Batalkan pemindaian</button>
                <button class="btn btn-cyan" :disabled="submitting">
                    <span x-show="!submitting">Check-in sekarang</span>
                    <span x-show="submitting" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Memproses&hellip;
                    </span>
                </button>
            </div>
        </form>
    </div>

    <div class="card card-pad">
        <p class="eyebrow">Status Kehadiran</p>
        <ul class="kartu-masuk mt-3 divide-y divide-slate-100">
            @forelse ($p->kegiatan->sesi as $s)
                <li class="flex items-center gap-3 py-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">Sesi {{ $s->urutan_sesi }} · {{ $s->judul_sesi }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $s->tanggal_sesi?->translatedFormat('d M Y') }} ·
                            {{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}
                        </p>
                    </div>
                    <span class="chip {{ in_array($s->id_sesi, $hadir) ? 'chip-ok' : 'chip-off' }} ml-auto">
                        {{ in_array($s->id_sesi, $hadir) ? 'Hadir' : 'Belum' }}
                    </span>
                </li>
            @empty
                <li class="py-3 text-sm text-slate-500">Belum ada sesi.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
