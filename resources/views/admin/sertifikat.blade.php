@extends('layouts.app')
@section('judul', 'Sertifikat')

@section('isi')
<form method="POST" action="{{ route('admin.sertifikat.massal') }}" class="card card-pad flex flex-wrap items-end gap-3">
    @csrf
    <div class="min-w-64">
        <label class="label">Terbitkan massal untuk kegiatan</label>
        <select class="select" name="id_kegiatan" required>
            @foreach ($kegiatan as $k)
                <option value="{{ $k->id_kegiatan }}">{{ $k->tema }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-cyan">Terbitkan</button>
    <p class="w-full text-xs text-slate-400">
        Hanya peserta yang hadir, menyelesaikan pre-test &amp; post-test, dan mengirim kuesioner yang diterbitkan.
    </p>
</form>

<div class="card mt-5 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Nomor</th><th>Peserta</th><th>Kegiatan</th><th>Kode verifikasi</th><th>Terbit</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($baris as $s)
            <tr>
                <td class="font-mono">{{ $s->nomor_sertifikat }}</td>
                <td class="font-semibold">{{ $s->pendaftaran->peserta->nama_peserta }}</td>
                <td>{{ $s->pendaftaran->kegiatan->tema }}</td>
                <td class="font-mono">{{ $s->kode_verifikasi }}</td>
                <td>{{ $s->diterbitkan_pada?->format('d/m/Y') }}</td>
                <td><span class="chip {{ $s->status_sertifikat === 'terbit' ? 'chip-ok' : 'chip-bad' }}">{{ $s->status_sertifikat }}</span></td>
                <td class="whitespace-nowrap text-right">
                    <a href="{{ route('verifikasi', ['kode' => $s->kode_verifikasi]) }}" class="btn btn-ghost btn-sm" target="_blank">Cek publik</a>
                    @if ($s->status_sertifikat === 'terbit')
                        <form method="POST" action="{{ route('admin.sertifikat.cabut', $s) }}" class="inline"
                              onsubmit="return confirm('Cabut sertifikat ini?')">
                            @csrf
                            <button class="btn btn-danger btn-sm">Cabut</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-sm text-slate-500">Belum ada sertifikat.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $baris->links() }}</div>
@endsection
