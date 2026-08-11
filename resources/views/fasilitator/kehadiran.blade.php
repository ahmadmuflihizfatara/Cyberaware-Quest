@extends('layouts.app')
@section('judul', 'Presensi Sesi')

@section('isi')
<div class="card card-pad">
    <p class="eyebrow">Sesi {{ $s->urutan_sesi }} · {{ $s->kegiatan->tema }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $s->judul_sesi }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        {{ $hadir->count() }} dari {{ $pendaftaran->count() }} pendaftar sudah hadir.
    </p>
</div>

<div class="card mt-5 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Peserta</th><th>Status pendaftaran</th><th>Kehadiran</th><th>Metode</th><th></th></tr></thead>
        <tbody>
        @forelse ($pendaftaran as $p)
            @php $h = $hadir[$p->id_pendaftaran] ?? null; @endphp
            <tr>
                <td class="font-semibold">{{ $p->peserta->nama_peserta }}</td>
                <td><span class="chip chip-off">{{ $p->status_pendaftaran }}</span></td>
                <td>
                    @if ($h)
                        <span class="chip chip-ok">Hadir {{ $h->waktu_hadir?->format('H:i') }}</span>
                    @else
                        <span class="chip chip-off">Belum</span>
                    @endif
                </td>
                <td class="text-slate-500">{{ $h->metode_presensi ?? '—' }}</td>
                <td class="text-right">
                    @unless ($h)
                        <form method="POST" action="{{ route('fasilitator.sesi.kehadiran.manual', $s) }}">
                            @csrf
                            <input type="hidden" name="id_pendaftaran" value="{{ $p->id_pendaftaran }}">
                            <button class="btn btn-ghost btn-sm">Tandai hadir</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-sm text-slate-500">Belum ada pendaftar.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
