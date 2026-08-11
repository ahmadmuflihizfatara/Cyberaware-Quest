@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Check-in Sesi')

@section('isi')
<div class="grid gap-5 lg:grid-cols-[1fr_1fr] lg:items-start">
    <div class="card card-pad">
        <p class="eyebrow">Tahap 3 · Check-in</p>
        <h2 class="mt-1 font-display text-2xl font-extrabold">Pindai QR sesi</h2>
        <p class="mt-1 text-sm text-slate-500">
            Token dibuka fasilitator dan berlaku terbatas. Bila kamera tidak tersedia, ketik token manual.
        </p>

        <video id="pratinjau-kamera" class="mt-4 hidden w-full rounded-xl bg-slate-900" playsinline muted></video>
        <p id="status-pindai" class="mt-3 text-sm text-slate-500">Menunggu pindaian token QR sesi.</p>

        <form method="POST" action="{{ route('peserta.checkin.simpan', $p) }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="label" for="token">Token sesi</label>
                <input class="input font-mono uppercase" id="token" name="token" value="{{ $token }}" required>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" id="mulai-pindai" class="btn btn-ghost">Pindai QR</button>
                <button class="btn btn-cyan">Check-in sekarang</button>
            </div>
        </form>
    </div>

    <div class="card card-pad">
        <p class="eyebrow">Status Kehadiran</p>
        <ul class="mt-3 divide-y divide-slate-100">
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
