@extends('layouts.app')
@section('judul', 'Kegiatan')

@section('isi')
<div class="grid gap-5 xl:grid-cols-[1.4fr_1fr] xl:items-start">
    <div>
        <h2 class="font-display text-2xl font-extrabold">Kegiatan / Angkatan</h2>
        <div class="card mt-4 tbl-wrap">
            <table class="tbl">
                <thead><tr><th>#</th><th>Tema</th><th>Program</th><th>Sekolah</th><th>Mulai</th><th>Mode</th><th>Kuota</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($kegiatan as $k)
                    <tr>
                        <td class="text-slate-400">{{ $k->id_kegiatan }}</td>
                        <td class="font-semibold">{{ $k->tema }}</td>
                        <td>{{ $k->program->nama_program }}</td>
                        <td>{{ $k->sekolah?->mitra?->nama_mitra }}</td>
                        <td>{{ $k->tanggal_mulai?->format('d/m/Y') }}</td>
                        <td>{{ $k->mode_pelaksanaan }}</td>
                        <td>{{ $k->pendaftaran_count }}/{{ $k->kapasitas }}</td>
                        <td><span class="chip {{ $k->status_kegiatan === 'berlangsung' ? 'chip-warn' : 'chip-off' }}">{{ $k->status_kegiatan }}</span></td>
                        <td class="text-right"><a href="{{ route('admin.kegiatan.show', $k) }}" class="btn btn-ghost btn-sm">Kelola</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-sm text-slate-500">Belum ada kegiatan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $kegiatan->links() }}</div>
    </div>

    <form method="POST" action="{{ route('admin.kegiatan.store') }}" class="card card-pad space-y-3">
        @csrf
        <h3 class="text-lg font-bold">Tambah kegiatan</h3>

        <div>
            <label class="label">Tema</label>
            <input class="input" name="tema" required value="{{ old('tema') }}">
        </div>
        <div>
            <label class="label">Program PkM</label>
            <select class="select" name="id_program" required>
                @foreach ($program as $p)
                    <option value="{{ $p->id_program }}">{{ $p->nama_program }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Sekolah</label>
            <select class="select" name="id_sekolah" required>
                @foreach ($sekolah as $s)
                    <option value="{{ $s->id_sekolah }}">{{ $s->mitra->nama_mitra }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Lokasi <span class="font-normal text-slate-400">(wajib untuk luring/hybrid)</span></label>
            <select class="select" name="id_lokasi">
                <option value="">— daring, tanpa lokasi —</option>
                @foreach ($lokasi as $l)
                    <option value="{{ $l->id_lokasi }}">{{ $l->sekolah->mitra->nama_mitra }} — {{ $l->nama_lokasi }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="label">Tanggal mulai</label>
                <input class="input" type="date" name="tanggal_mulai" required value="{{ old('tanggal_mulai') }}">
            </div>
            <div>
                <label class="label">Tanggal selesai</label>
                <input class="input" type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}">
            </div>
            <div>
                <label class="label">Kapasitas</label>
                <input class="input" type="number" name="kapasitas" min="1" required value="{{ old('kapasitas', 40) }}">
            </div>
            <div>
                <label class="label">Mode</label>
                <select class="select" name="mode_pelaksanaan" required>
                    @foreach (['luring', 'daring', 'hybrid'] as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="label">Status</label>
            <select class="select" name="status_kegiatan" required>
                @foreach (['terjadwal', 'berlangsung', 'selesai', 'dibatalkan'] as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary w-full">Tambah kegiatan</button>
    </form>
</div>
@endsection
