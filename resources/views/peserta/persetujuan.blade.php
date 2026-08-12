@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Persetujuan Pengolahan Data')

@php
    $kategoriData = [
        ['Identitas', 'M9 7a4 4 0 11-8 0 4 4 0 018 0zM1 21a7 7 0 0114 0H1z'],
        ['Afiliasi', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m6-14h2m-2 4h2m-2 4h2'],
        ['Demografi', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['Kehadiran', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['Jawaban tes', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Aktivitas', 'M13 10V3L4 14h7v7l9-11h-7z'],
        ['Evaluasi', 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
    ];
@endphp

@section('isi')
<a href="{{ route('peserta.pendaftaran.show', $p) }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Kembali ke tahapan</a>

<div class="animasi-masuk card card-pad mt-4 max-w-3xl">
    @if ($p->persetujuan?->disetujui)
        <div class="flex items-start gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </span>
            <div>
                <p class="eyebrow">Tahap 1 &middot; Selesai</p>
                <h2 class="mt-1 font-display text-2xl font-extrabold">Persetujuan tercatat</h2>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                    <span class="chip chip-ok">Versi kebijakan {{ $p->persetujuan->versi_kebijakan }}</span>
                    <span>Disetujui {{ $p->persetujuan->waktu_persetujuan?->translatedFormat('d F Y, H:i') }}</span>
                </div>
            </div>
        </div>
        <a href="{{ route('peserta.instrumen', [$p, 'demografi']) }}" class="btn btn-cyan mt-6">Lanjut ke demografi</a>
    @else
        <div class="field-masuk space-y-5" x-data="{ setuju: false, submitting: false }">
            <div>
                <p class="eyebrow">Tahap 1</p>
                <h2 class="mt-1 font-display text-2xl font-extrabold">Persetujuan pengolahan data</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Sebelum melanjutkan pendaftaran <strong>{{ $p->kegiatan->tema }}</strong>, baca dan
                    setujui bagaimana data Anda digunakan.
                </p>
            </div>

            <div class="card card-pad bg-slate-50">
                <p class="eyebrow">Kategori data yang dikumpulkan</p>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($kategoriData as [$label, $path])
                        <div class="flex items-center gap-2 rounded-lg bg-white px-2.5 py-2 text-xs font-semibold text-slate-600 shadow-sm">
                            <svg class="h-4 w-4 shrink-0 text-cyan-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                            </svg>
                            {{ $label }}
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="text-sm leading-relaxed text-slate-600">
                Hasil evaluasi penyelenggaraan dapat dilaporkan secara agregat. Bila panitia menetapkan mode
                kuesioner anonim, identitas Anda tidak ditampilkan pada laporan evaluasi.
                <a href="{{ route('kebijakan-privasi') }}" target="_blank" rel="noopener"
                   class="font-semibold text-navy-700 hover:underline">Baca kebijakan privasi lengkap &rarr;</a>
            </p>

            <form method="POST" action="{{ route('peserta.persetujuan.simpan', $p) }}" class="space-y-4" @submit="submitting = true">
                @csrf
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-cyan-200 bg-cyan-50/50 p-4 text-sm text-slate-700">
                    <input type="checkbox" name="setuju" value="1" class="mt-0.5 h-4 w-4" required x-model="setuju">
                    <span>
                        Saya menyetujui pengolahan data sebagaimana dijelaskan di atas, sesuai
                        <span class="font-semibold">versi kebijakan 1.0</span>.
                    </span>
                </label>

                <button class="btn btn-cyan" :disabled="!setuju || submitting">
                    <span x-show="!submitting" class="inline-flex items-center gap-2">Setujui &amp; lanjutkan</span>
                    <span x-show="submitting" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Memproses&hellip;
                    </span>
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
