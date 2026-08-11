@extends('layouts.app')
@section('judul', 'Butir Instrumen')

@section('isi')
<div class="card card-pad">
    <p class="eyebrow">{{ $v->instrumen->nama_instrumen }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">Versi {{ $v->nomor_versi }}</h2>
    <div class="mt-2 flex flex-wrap items-center gap-2">
        <span class="chip {{ $v->status_versi === 'draft' ? 'chip-warn' : 'chip-ok' }}">{{ $v->status_versi }}</span>
        @if ($terpakai)<span class="chip chip-info">sudah dipakai pelaksanaan</span>@endif
    </div>
    @if ($v->terkunci())
        <p class="mt-3 text-sm text-slate-600">
            Versi terkunci tidak dapat diubah. Buat versi baru bila butir perlu direvisi.
        </p>
    @endif
</div>

<div class="mt-5 space-y-3">
    @forelse ($butir as $b)
        <div class="card card-pad">
            <div class="flex flex-wrap items-start gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[0.6rem] font-bold uppercase tracking-[0.12em] text-slate-400">
                        Butir {{ $b->nomor_urut }} · {{ str_replace('_', ' ', $b->tipe_butir) }} ·
                        bobot {{ rtrim(rtrim(number_format($b->bobot_skor, 2), '0'), '.') }} ·
                        {{ $b->wajib_diisi ? 'wajib' : 'opsional' }}
                    </p>
                    <p class="mt-1 font-semibold">{{ $b->teks_butir }}</p>
                    @if ($b->opsi->isNotEmpty())
                        <ul class="mt-2 space-y-1 text-sm text-slate-600">
                            @foreach ($b->opsi as $o)
                                <li>
                                    {{ $o->urutan_opsi }}. {{ $o->teks_opsi }}
                                    <span class="text-xs text-slate-400">(nilai {{ rtrim(rtrim(number_format($o->nilai_skor, 2), '0'), '.') }})</span>
                                    @if ($o->kunci_jawaban)<span class="chip chip-ok ml-1">kunci</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @unless ($v->terkunci())
                    <form method="POST" action="{{ route('admin.instrumen.butir.hapus', $b) }}" onsubmit="return confirm('Hapus butir ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                @endunless
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada butir.</p>
    @endforelse
</div>

@unless ($v->terkunci())
    <form method="POST" action="{{ route('admin.instrumen.butir.simpan', $v) }}" class="card card-pad mt-5 grid gap-3 sm:grid-cols-3">
        @csrf
        <div class="sm:col-span-3">
            <label class="label">Teks butir</label>
            <textarea class="textarea" name="teks_butir" required></textarea>
        </div>
        <div>
            <label class="label">Tipe butir</label>
            <select class="select" name="tipe_butir" required>
                @foreach (['pilihan_ganda', 'skala_likert', 'esai', 'isian_singkat'] as $t)
                    <option value="{{ $t }}">{{ str_replace('_', ' ', $t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Bobot skor</label>
            <input class="input" type="number" step="0.01" min="0" name="bobot_skor" value="10" required>
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="wajib_diisi" value="1" checked> Wajib diisi
            </label>
        </div>

        <div class="sm:col-span-3">
            <p class="label">Opsi jawaban <span class="font-normal text-slate-400">(untuk pilihan ganda / skala Likert)</span></p>
            <div class="space-y-2">
                @for ($i = 0; $i < 5; $i++)
                    <div class="grid gap-2 sm:grid-cols-[auto_1fr_8rem]">
                        <label class="flex items-center gap-2 text-xs text-slate-500">
                            <input type="radio" name="kunci" value="{{ $i }}"> kunci
                        </label>
                        <input class="input" name="opsi[{{ $i }}][teks]" placeholder="Teks opsi {{ $i + 1 }}">
                        <input class="input" type="number" step="0.01" name="opsi[{{ $i }}][nilai]" placeholder="nilai" value="0">
                    </div>
                @endfor
            </div>
        </div>

        <div class="sm:col-span-3"><button class="btn btn-primary">Tambah butir</button></div>
    </form>

    <form method="POST" action="{{ route('admin.instrumen.publikasi', $v) }}" class="mt-4"
          onsubmit="return confirm('Kunci versi ini? Butir tidak dapat diubah lagi.')">
        @csrf
        <button class="btn btn-cyan">Publikasikan &amp; kunci versi</button>
    </form>
@endunless

<a href="{{ route('admin.instrumen.versi', $v->instrumen) }}" class="btn btn-ghost mt-4">&larr; Daftar versi</a>
@endsection
