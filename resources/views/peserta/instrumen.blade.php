@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', ucfirst($fase))

@php
    $labelFase = [
        'demografi' => ['Tahap 1 · Demografi', 'Data demografi dicatat per pendaftaran dan boleh berbeda antarprogram.'],
        'pretest' => ['Tahap 2 · Pre-test', 'Skor disembunyikan hingga post-test selesai.'],
        'posttest' => ['Tahap 4 · Post-test', 'Paket soal sama dengan pre-test agar selisih dapat dibandingkan.'],
        'kuesioner' => ['Tahap 5 · Kuesioner', 'Evaluasi penyelenggaraan; tidak menghasilkan skor pembelajaran.'],
    ][$fase];
    $wajibIds = $butir->where('wajib_diisi', true)->pluck('id_butir')->values();
@endphp

@section('isi')
<a href="{{ route('peserta.pendaftaran.show', $p) }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Kembali ke tahapan</a>

<div class="animasi-masuk mt-4 max-w-3xl"
     x-data="{
        jawaban: {
            @foreach ($butir as $b)
                {{ $b->id_butir }}: @js(old('jawaban.'.$b->id_butir, '')),
            @endforeach
        },
        wajibIds: @js($wajibIds),
        submitting: false,
        get terjawab() {
            return this.wajibIds.filter(id => String(this.jawaban[id] ?? '').trim() !== '').length;
        },
        get persen() {
            return this.wajibIds.length ? Math.round((this.terjawab / this.wajibIds.length) * 100) : 100;
        },
        autoResize(el) { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; },
     }">
    <p class="eyebrow">{{ $labelFase[0] }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $pelaksanaan->versi->instrumen->nama_instrumen }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        {{ $butir->count() }} butir · versi {{ $pelaksanaan->versi->nomor_versi }} · {{ $labelFase[1] }}
    </p>

    @if ($wajibIds->isNotEmpty())
        <div class="mt-4 flex items-center gap-3">
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-cyan-500 transition-all duration-300" :style="`width: ${persen}%`"></div>
            </div>
            <span class="whitespace-nowrap text-xs font-semibold text-slate-500">
                <span x-text="terjawab"></span>/{{ $wajibIds->count() }} wajib terjawab
            </span>
        </div>
    @endif

    @if ($anonim)
        <div class="card card-pad mt-4 border-cyan-200 bg-cyan-50/50 text-sm text-cyan-900">
            Mode evaluasi <strong>anonim</strong>: jawaban Anda tidak ditampilkan bersama identitas pada laporan.
        </div>
    @endif

    <form method="POST" action="{{ route('peserta.instrumen.kirim', [$p, $fase]) }}" class="kartu-masuk mt-6 space-y-4"
          @submit="if (! confirm('Kirim sebagai jawaban final? Respons tidak dapat diubah setelah dikirim.')) { $event.preventDefault(); return; } submitting = true">
        @csrf
        @foreach ($butir as $b)
            <fieldset class="card card-pad">
                <legend class="sr-only">Butir {{ $b->nomor_urut }}</legend>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-[0.6rem] font-bold uppercase tracking-[0.12em] text-slate-400">
                        Soal {{ $b->nomor_urut }} dari {{ $butir->count() }}
                    </p>
                    @if ($b->wajib_diisi)
                        <span class="chip"
                              :class="String(jawaban[{{ $b->id_butir }}] ?? '').trim() !== '' ? 'chip-ok' : 'chip-warn'"
                              x-text="String(jawaban[{{ $b->id_butir }}] ?? '').trim() !== '' ? 'Terisi' : 'Wajib'"></span>
                    @endif
                </div>
                <p class="mt-1.5 font-semibold leading-snug">{{ $b->teks_butir }}</p>

                @if (in_array($b->tipe_butir, ['pilihan_ganda', 'skala_likert']))
                    <div class="mt-3 space-y-2">
                        @foreach ($b->opsi as $o)
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2.5 text-sm transition-colors duration-150"
                                   :class="String(jawaban[{{ $b->id_butir }}]) === '{{ $o->id_opsi }}' ? 'border-cyan-500 bg-cyan-50/60' : 'border-line hover:border-cyan-300'">
                                <input type="radio" class="mt-0.5" name="jawaban[{{ $b->id_butir }}]"
                                       value="{{ $o->id_opsi }}" x-model="jawaban[{{ $b->id_butir }}]"
                                       @if ($b->wajib_diisi) required @endif>
                                <span>{{ $o->teks_opsi }}</span>
                            </label>
                        @endforeach
                    </div>
                @elseif ($b->tipe_butir === 'esai')
                    <textarea class="textarea mt-3" name="jawaban[{{ $b->id_butir }}]" rows="3"
                              placeholder="Tulis jawaban Anda" x-model="jawaban[{{ $b->id_butir }}]"
                              x-init="$nextTick(() => autoResize($el))" @input="autoResize($el)"
                              @if ($b->wajib_diisi) required @endif></textarea>
                @else
                    <input class="input mt-3" name="jawaban[{{ $b->id_butir }}]" x-model="jawaban[{{ $b->id_butir }}]"
                           placeholder="Jawaban singkat" @if ($b->wajib_diisi) required @endif>
                @endif
            </fieldset>
        @endforeach

        <div class="card card-pad flex items-start gap-3 border-amber-200 bg-amber-50/60">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <p class="text-sm text-amber-900">
                Setelah dikirim, respons ini ditandai <strong>final</strong> dan tidak dapat diubah lagi.
            </p>
        </div>

        <button class="btn btn-cyan" :disabled="submitting">
            <span x-show="!submitting" class="inline-flex items-center gap-2">Kirim jawaban final</span>
            <span x-show="submitting" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Mengirim&hellip;
            </span>
        </button>
    </form>
</div>
@endsection
