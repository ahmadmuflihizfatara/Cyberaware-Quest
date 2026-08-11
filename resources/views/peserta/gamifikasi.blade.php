@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Aktivitas & Tantangan Berpoin')

@section('isi')
<div class="card card-pad border-cyan-200 bg-cyan-50/50">
    <p class="text-sm text-cyan-900">
        Poin gamifikasi terpisah dari skor tes — tidak memengaruhi hasil pre-test/post-test.
        Saldo poin Anda saat ini: <strong>{{ $saldo }}</strong>.
    </p>
</div>

<div class="mt-5 grid gap-4 md:grid-cols-2">
    @forelse ($daftar as $g)
        @php $sudah = $partisipasi[$g->id_gamifikasi] ?? null; @endphp
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip chip-info">{{ str_replace('_', ' ', $g->jenis_gamifikasi) }}</span>
                <span class="chip">Maks. {{ $g->poin_maksimal }} poin</span>
            </div>
            <h3 class="mt-2 text-lg font-bold">{{ $g->judul_gamifikasi }}</h3>
            <p class="mt-1 text-xs text-slate-400">Sesi {{ $g->sesi->urutan_sesi }} · {{ $g->sesi->judul_sesi }}</p>

            @if ($sudah)
                <p class="mt-3 text-sm font-semibold text-emerald-700">
                    Selesai · {{ $sudah->skor_permainan }} poin diperoleh
                    ({{ $sudah->waktu_selesai?->translatedFormat('d M Y H:i') }})
                </p>
                <p class="mt-1 text-xs text-slate-400">Poin satu aktivitas tidak diberikan dua kali.</p>
            @else
                <form method="POST" action="{{ route('peserta.gamifikasi.ikut', [$p, $g]) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                    @csrf
                    <div>
                        <label class="label">Jawaban benar</label>
                        <input class="input" type="number" name="benar" min="0" max="100" value="0" required>
                    </div>
                    <div>
                        <label class="label">Total soal / tahap</label>
                        <input class="input" type="number" name="total" min="1" max="100" value="10" required>
                    </div>
                    <div class="flex items-end"><button class="btn btn-cyan w-full">Kirim hasil</button></div>
                </form>
                <p class="mt-2 text-xs text-slate-400">
                    Poin dihitung server: proporsi jawaban benar × poin maksimal.
                </p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada aktivitas gamifikasi pada kegiatan ini.</p>
    @endforelse
</div>

<h3 class="mt-8 text-lg font-bold">Leaderboard</h3>
@include('peserta.partials.leaderboard', ['leaderboard' => $leaderboard, 'p' => $p])
@endsection
