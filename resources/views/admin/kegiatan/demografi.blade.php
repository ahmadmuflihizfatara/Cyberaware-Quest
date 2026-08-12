@extends('layouts.app')
@section('judul', 'Jawaban Demografi')

@section('isi')
<a href="{{ route('admin.kegiatan.show', $k) }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Kembali ke kegiatan</a>

<div class="animasi-masuk mt-4">
    <p class="eyebrow">{{ $k->tema }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">Jawaban Demografi Peserta</h2>
    <p class="mt-1 text-sm text-slate-500">{{ $respons->count() }} respons final dari peserta yang sudah mengisi.</p>

    <div class="card mt-4 tbl-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Peserta</th>
                    @foreach ($butir as $b)
                        <th>{{ $b->teks_butir }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="baris-masuk">
            @forelse ($respons as $r)
                <tr>
                    <td class="font-semibold">{{ $r->pendaftaran->peserta->nama_peserta }}</td>
                    @foreach ($butir as $b)
                        @php
                            $jawaban = $r->jawaban->firstWhere('id_butir', $b->id_butir);
                        @endphp
                        <td class="text-slate-600">
                            {{ $jawaban?->opsi?->teks_opsi ?? $jawaban?->teks_jawaban ?? '—' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($butir) + 1 }}" class="text-sm text-slate-500">Belum ada respons demografi final.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
