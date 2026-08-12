@extends('layouts.app')
@section('judul', 'Data Demografi: ' . $k->tema)

@section('isi')
<div class="mb-4">
    <a href="{{ route('admin.kegiatan.show', $k) }}" class="text-sm text-cyan-600 hover:underline">&larr; Kembali ke Detail Kegiatan</a>
</div>

<div class="card card-pad">
    <h2 class="font-display text-2xl font-extrabold">Data Demografi</h2>
    <p class="mt-1 text-slate-500">Menampilkan respons instrumen tahap demografi dari semua peserta kegiatan <strong>{{ $k->tema }}</strong>.</p>
</div>

<div class="card mt-6 tbl-wrap">
    <table class="tbl text-sm">
        <thead>
            <tr>
                <th class="w-12 text-center">#</th>
                <th>Peserta</th>
                @foreach ($butir as $b)
                    <th>{{ $b->teks_butir }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($respons as $r)
                <tr>
                    <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                    <td class="font-semibold whitespace-nowrap">{{ $r->pendaftaran->peserta->nama_peserta }}</td>
                    @foreach ($butir as $b)
                        @php
                            $jawab = $r->jawaban->firstWhere('id_butir', $b->id_butir);
                        @endphp
                        <td>
                            @if ($jawab)
                                {{ $jawab->teks_jawaban ?: '—' }}
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 2 + $butir->count() }}" class="text-center text-slate-500 py-6">
                        Belum ada peserta yang menyelesaikan form demografi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
