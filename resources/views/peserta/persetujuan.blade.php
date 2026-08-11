@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Persetujuan Pengolahan Data')

@section('isi')
<div class="card card-pad max-w-3xl">
    <p class="eyebrow">Tahap 1</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">Persetujuan pengolahan data</h2>

    <div class="prose-ringkas mt-4 text-sm text-slate-600">
        <p>
            Data yang Anda isikan pada kegiatan <strong>{{ $p->kegiatan->tema }}</strong> digunakan untuk
            keperluan penyelenggaraan dan pelaporan program Pengabdian kepada Masyarakat: identitas dasar,
            afiliasi, demografi, kehadiran per sesi, jawaban pre-test/post-test, aktivitas, dan evaluasi.
        </p>
        <p>
            Hasil evaluasi penyelenggaraan dapat dilaporkan secara agregat. Bila panitia menetapkan mode
            kuesioner anonim, identitas Anda tidak ditampilkan pada laporan evaluasi.
        </p>
        <p>Versi kebijakan: <strong>1.0</strong></p>
    </div>

    @if ($p->persetujuan?->disetujui)
        <p class="mt-5 text-sm font-semibold text-emerald-700">
            Sudah disetujui pada {{ $p->persetujuan->waktu_persetujuan?->translatedFormat('d F Y H:i') }}.
        </p>
        <a href="{{ route('peserta.instrumen', [$p, 'demografi']) }}" class="btn btn-cyan mt-4">Lanjut ke demografi</a>
    @else
        <form method="POST" action="{{ route('peserta.persetujuan.simpan', $p) }}" class="mt-5 space-y-4">
            @csrf
            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="setuju" value="1" class="mt-1" required>
                Saya menyetujui pengolahan data sebagaimana dijelaskan di atas.
            </label>
            <button class="btn btn-cyan">Setujui &amp; lanjutkan</button>
        </form>
    @endif
</div>
@endsection
