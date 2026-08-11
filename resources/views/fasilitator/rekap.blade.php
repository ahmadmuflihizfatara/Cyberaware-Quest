@extends('layouts.app')
@section('judul', 'Rekap Nilai Peserta')

@section('isi')
<p class="text-sm text-slate-500">
    Skor pre-test dan post-test peserta pada kegiatan yang Anda tangani. Sumber: view <code>v_hasil_belajar</code>.
</p>

<div class="card mt-4 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Peserta</th><th>Kegiatan</th><th>Status</th><th class="text-right">Pre-test</th><th class="text-right">Post-test</th><th class="text-right">Selisih</th></tr></thead>
        <tbody>
        @forelse ($baris as $b)
            <tr>
                <td class="font-semibold">{{ $b->nama_peserta }}</td>
                <td>{{ $b->tema }}</td>
                <td><span class="chip chip-off">{{ $b->status_pendaftaran }}</span></td>
                <td class="text-right">{{ $b->skor_pretest ?? '—' }}</td>
                <td class="text-right">{{ $b->skor_posttest ?? '—' }}</td>
                <td class="text-right font-semibold {{ ($b->selisih_skor ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $b->selisih_skor !== null ? ($b->selisih_skor > 0 ? '+' : '').$b->selisih_skor : '—' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-sm text-slate-500">Belum ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
