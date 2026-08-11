@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Aktivitas & Artefak')

@section('isi')
<div class="space-y-4">
    @forelse ($aktivitas as $a)
        @php
            $part = $partisipasi[$a->id_aktivitas] ?? null;
            $artefak = $part?->artefak;
        @endphp
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip chip-info">{{ str_replace('_', ' ', $a->jenis_aktivitas) }}</span>
                @if ($a->tool_ai)<span class="chip">Tool: {{ $a->tool_ai }}</span>@endif
                <span class="text-xs text-slate-400">Sesi {{ $a->sesi->urutan_sesi }} · {{ $a->sesi->judul_sesi }}</span>
            </div>
            <h3 class="mt-2 text-lg font-bold">{{ $a->judul_aktivitas }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $a->deskripsi }}</p>

            @if ($a->jenis_aktivitas === 'tugas_artefak')
                @if ($artefak)
                    <div class="mt-4 rounded-xl border border-line p-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip {{ ['menunggu' => 'chip-warn', 'terverifikasi' => 'chip-ok', 'ditolak' => 'chip-bad'][$artefak->status_verifikasi] }}">
                                {{ $artefak->status_verifikasi }}
                            </span>
                            <span class="font-semibold">{{ $artefak->judul_artefak }}</span>
                            <a href="{{ $artefak->tautan_atau_file }}" target="_blank" rel="noopener"
                               class="text-sm font-semibold text-navy-700 hover:underline">Buka artefak</a>
                        </div>
                        @if ($artefak->catatan_revisi)
                            <p class="mt-2 text-sm text-amber-700">Catatan fasilitator: {{ $artefak->catatan_revisi }}</p>
                        @endif
                    </div>
                @endif

                <form method="POST" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-2"
                      action="{{ route('peserta.artefak.simpan', [$p, $a]) }}">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="label">Judul artefak</label>
                        <input class="input" name="judul_artefak" required value="{{ $artefak->judul_artefak ?? '' }}">
                    </div>
                    <div>
                        <label class="label">Tipe</label>
                        <select class="select" name="tipe_file" required>
                            @foreach (['link', 'image', 'pdf', 'video', 'document'] as $t)
                                <option value="{{ $t }}" @selected(($artefak->tipe_file ?? 'link') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Tautan (mis. Canva/Gamma)</label>
                        <input class="input" name="tautan" type="url" placeholder="https://…">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">atau unggah berkas <span class="font-normal text-slate-400">(maks. 20 MB)</span></label>
                        <input class="input" name="berkas" type="file"
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4,.doc,.docx,.ppt,.pptx">
                    </div>
                    <div class="sm:col-span-2">
                        <button class="btn btn-cyan">{{ $artefak ? 'Perbarui artefak' : 'Kirim artefak' }}</button>
                    </div>
                </form>
            @else
                <p class="mt-3 text-xs text-slate-400">
                    Aktivitas ini tidak berpoin. Poin hanya berasal dari aktivitas gamifikasi.
                </p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada aktivitas pembelajaran pada kegiatan ini.</p>
    @endforelse
</div>
@endsection
