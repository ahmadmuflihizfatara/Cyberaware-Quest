@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Hasil '.ucfirst($fase))

@section('isi')
<div class="max-w-3xl">
    <p class="eyebrow">Tahap selesai</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">Respons {{ $fase }} sudah final</h2>
    <p class="mt-1 text-sm text-slate-500">
        Dikirim {{ $respons->selesai_pada?->translatedFormat('d F Y H:i') }} · percobaan ke-{{ $respons->percobaan_ke }}
    </p>

    @if (in_array($fase, ['pretest', 'posttest']))
        @php $adaPost = $hasilBelajar?->skor_posttest !== null; @endphp

        @if ($fase === 'pretest' && ! $adaPost)
            <div class="card card-pad mt-5">
                <p class="text-sm text-slate-600">
                    Skor pre-test disembunyikan hingga post-test selesai, supaya tidak memengaruhi
                    cara Anda mengikuti materi.
                </p>
            </div>
        @else
            <div class="card card-pad mt-5">
                <p class="eyebrow">Ringkasan Hasil Belajar</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                    <div><p class="stat-label">Pre-test</p><p class="stat-value">{{ $hasilBelajar?->skor_pretest ?? '—' }}</p></div>
                    <div><p class="stat-label">Post-test</p><p class="stat-value">{{ $hasilBelajar?->skor_posttest ?? '—' }}</p></div>
                    <div>
                        <p class="stat-label">Selisih</p>
                        <p class="stat-value {{ ($hasilBelajar?->selisih_skor ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $hasilBelajar?->selisih_skor !== null ? ($hasilBelajar->selisih_skor > 0 ? '+' : '').$hasilBelajar->selisih_skor : '—' }}
                        </p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-400">
                    Sumber: view <code>v_hasil_belajar</code>. Poin gamifikasi tidak dihitung di sini.
                </p>
            </div>
        @endif
    @else
        <div class="card card-pad mt-5 text-sm text-slate-600">
            Terima kasih. Respons {{ $fase }} tidak menghasilkan skor pembelajaran.
        </div>
    @endif

    <a href="{{ route('peserta.pendaftaran.show', $p) }}" class="btn btn-ghost mt-5">Kembali ke tahapan</a>
</div>
@endsection
