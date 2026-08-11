@extends('layouts.app')
@section('judul', 'Aktivitas Sesi')

@section('isi')
<p class="eyebrow">Sesi {{ $s->urutan_sesi }}</p>
<h2 class="mt-1 font-display text-2xl font-extrabold">{{ $s->judul_sesi }}</h2>

<div class="mt-6 grid gap-5 lg:grid-cols-2 lg:items-start">
    <div>
        <h3 class="text-lg font-bold">Aktivitas pembelajaran <span class="text-sm font-normal text-slate-400">(tanpa poin)</span></h3>
        <div class="card mt-3 tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Judul</th><th>Jenis</th><th>Tool AI</th><th class="text-right">Partisipasi</th></tr></thead>
                <tbody>
                @forelse ($aktivitas as $a)
                    <tr>
                        <td class="font-semibold">{{ $a->judul_aktivitas }}</td>
                        <td>{{ str_replace('_', ' ', $a->jenis_aktivitas) }}</td>
                        <td>{{ $a->tool_ai ?? '—' }}</td>
                        <td class="text-right">{{ $a->partisipasi_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-sm text-slate-500">Belum ada aktivitas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('fasilitator.sesi.aktivitas.simpan', $s) }}" class="card card-pad mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label class="label">Judul aktivitas</label>
                <input class="input" name="judul_aktivitas" required>
            </div>
            <div>
                <label class="label">Jenis</label>
                <select class="select" name="jenis_aktivitas" required>
                    @foreach (['materi_bacaan', 'diskusi', 'tugas_artefak'] as $j)
                        <option value="{{ $j }}">{{ str_replace('_', ' ', $j) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Tool AI</label>
                <select class="select" name="tool_ai">
                    <option value="">— tanpa tool —</option>
                    @foreach (['canva', 'napkin', 'gamma', 'notebooklm', 'capcut', 'lainnya'] as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Deskripsi</label>
                <textarea class="textarea" name="deskripsi"></textarea>
            </div>
            <div class="sm:col-span-2"><button class="btn btn-primary">Tambah aktivitas</button></div>
        </form>
    </div>

    <div>
        <h3 class="text-lg font-bold">Aktivitas gamifikasi <span class="text-sm font-normal text-slate-400">(berpoin)</span></h3>
        <div class="card mt-3 tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Judul</th><th>Jenis</th><th>Maks. poin</th><th class="text-right">Peserta</th></tr></thead>
                <tbody>
                @forelse ($gamifikasi as $g)
                    <tr>
                        <td class="font-semibold">{{ $g->judul_gamifikasi }}</td>
                        <td>{{ str_replace('_', ' ', $g->jenis_gamifikasi) }}</td>
                        <td>{{ $g->poin_maksimal }}</td>
                        <td class="text-right">{{ $g->partisipasi_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-sm text-slate-500">Belum ada aktivitas gamifikasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('fasilitator.sesi.gamifikasi.simpan', $s) }}" class="card card-pad mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label class="label">Judul gamifikasi</label>
                <input class="input" name="judul_gamifikasi" required>
            </div>
            <div>
                <label class="label">Jenis</label>
                <select class="select" name="jenis_gamifikasi" required>
                    @foreach (['kuis_praktik', 'game', 'tantangan'] as $j)
                        <option value="{{ $j }}">{{ str_replace('_', ' ', $j) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Poin maksimal</label>
                <input class="input" type="number" name="poin_maksimal" value="100" min="0" max="1000" required>
            </div>
            <div class="sm:col-span-2"><button class="btn btn-primary">Tambah gamifikasi</button></div>
        </form>

        @if ($gamifikasi->isNotEmpty())
            <h3 class="mt-7 text-lg font-bold">Koreksi poin peserta</h3>
            <p class="mt-1 text-xs text-slate-400">Tercatat sebagai transaksi jenis <code>koreksi</code> dengan alasan wajib.</p>
            @foreach ($gamifikasi as $g)
                <form method="POST" action="{{ route('fasilitator.gamifikasi.nilai', $g) }}" class="card card-pad mt-3 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <p class="sm:col-span-2 font-semibold">{{ $g->judul_gamifikasi }}</p>
                    <div>
                        <label class="label">Peserta</label>
                        <select class="select" name="id_pendaftaran" required>
                            @foreach ($pendaftaran as $p)
                                <option value="{{ $p->id_pendaftaran }}">{{ $p->peserta->nama_peserta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Poin (boleh negatif, tidak boleh 0)</label>
                        <input class="input" type="number" name="poin" value="{{ $g->poin_maksimal }}" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Alasan</label>
                        <input class="input" name="keterangan" required placeholder="mis. penyesuaian penilaian juri">
                    </div>
                    <div class="sm:col-span-2"><button class="btn btn-cyan btn-sm">Catat poin</button></div>
                </form>
            @endforeach
        @endif
    </div>
</div>
@endsection
