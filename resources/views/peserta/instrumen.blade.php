@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', ucfirst($fase))

@php
    $labelFase = [
        'demografi' => ['Tahap 1 · Demografi', 'Data demografi dicatat per pendaftaran dan boleh berbeda antarprogram.'],
        'pretest' => ['Tahap 2 · Pre-test', 'Skor disembunyikan hingga post-test selesai.'],
        'posttest' => ['Tahap 4 · Post-test', 'Paket soal sama dengan pre-test agar selisih dapat dibandingkan.'],
        'kuesioner' => ['Tahap 5 · Kuesioner', 'Evaluasi penyelenggaraan; tidak menghasilkan skor pembelajaran.'],
    ][$fase];
@endphp

@section('isi')
<div class="max-w-3xl">
    <p class="eyebrow">{{ $labelFase[0] }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $pelaksanaan->versi->instrumen->nama_instrumen }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        {{ $butir->count() }} butir · versi {{ $pelaksanaan->versi->nomor_versi }} · {{ $labelFase[1] }}
    </p>

    @if ($anonim)
        <div class="card card-pad mt-4 border-cyan-200 bg-cyan-50/50 text-sm text-cyan-900">
            Mode evaluasi <strong>anonim</strong>: jawaban Anda tidak ditampilkan bersama identitas pada laporan.
        </div>
    @endif

    <form method="POST" action="{{ route('peserta.instrumen.kirim', [$p, $fase]) }}" class="mt-6 space-y-4">
        @csrf
        @foreach ($butir as $b)
            <fieldset class="card card-pad">
                <legend class="sr-only">Butir {{ $b->nomor_urut }}</legend>
                <p class="text-[0.6rem] font-bold uppercase tracking-[0.12em] text-slate-400">
                    Soal {{ $b->nomor_urut }} dari {{ $butir->count() }}
                    @if ($b->wajib_diisi) · wajib @endif
                </p>
                <p class="mt-1.5 font-semibold leading-snug">{{ $b->teks_butir }}</p>

                @if (in_array($b->tipe_butir, ['pilihan_ganda', 'skala_likert']))
                    <div class="mt-3 space-y-2">
                        @foreach ($b->opsi as $o)
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-line px-3 py-2.5 text-sm hover:border-cyan-500">
                                <input type="radio" class="mt-0.5" name="jawaban[{{ $b->id_butir }}]"
                                       value="{{ $o->id_opsi }}" @checked(old('jawaban.'.$b->id_butir) == $o->id_opsi)>
                                <span>{{ $o->teks_opsi }}</span>
                            </label>
                        @endforeach
                    </div>
                @elseif ($b->tipe_butir === 'esai')
                    <textarea class="textarea mt-3" name="jawaban[{{ $b->id_butir }}]"
                              placeholder="Tulis jawaban Anda">{{ old('jawaban.'.$b->id_butir) }}</textarea>
                @else
                    <input class="input mt-3" name="jawaban[{{ $b->id_butir }}]"
                           value="{{ old('jawaban.'.$b->id_butir) }}" placeholder="Jawaban singkat">
                @endif
            </fieldset>
        @endforeach

        <div class="flex flex-wrap items-center gap-3">
            <button class="btn btn-cyan">Kirim jawaban final</button>
            <p class="text-xs text-slate-500">Setelah dikirim, respons ditandai final dan tidak dapat diubah.</p>
        </div>
    </form>
</div>
@endsection
