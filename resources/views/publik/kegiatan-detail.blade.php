@extends('layouts.publik')
@section('judul', $kegiatan->tema)

@section('isi')
<a href="{{ route('kegiatan.index') }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Semua kegiatan</a>

<div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start">
    <div>
        <p class="eyebrow">Detail Kegiatan</p>
        <h1 class="mt-1 text-3xl font-bold leading-tight">{{ $kegiatan->tema }}</h1>
        <p class="mt-2 text-slate-500">
            Program {{ $kegiatan->program?->nama_program }} · {{ $kegiatan->sekolah?->mitra?->nama_mitra }}
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            <span class="chip chip-info">{{ ucfirst($kegiatan->mode_pelaksanaan) }}</span>
            @if ($kegiatan->lokasi)
                <span class="chip">{{ $kegiatan->lokasi->nama_lokasi }}</span>
            @endif
            <span class="chip {{ $kegiatan->status_kegiatan === 'berlangsung' ? 'chip-warn' : 'chip-off' }}">{{ $kegiatan->status_kegiatan }}</span>
        </div>

        <div class="card card-pad mt-6">
            <p class="eyebrow">Jadwal · {{ $kegiatan->tanggal_mulai?->translatedFormat('d F Y') }}</p>
            <ul class="mt-3 divide-y divide-slate-100">
                @forelse ($kegiatan->sesi as $s)
                    <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1 py-3">
                        <span class="font-semibold">Sesi {{ $s->urutan_sesi }} · {{ $s->judul_sesi }}</span>
                        <span class="text-sm text-slate-500">
                            {{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}
                            @if ($s->fasilitator) · {{ $s->fasilitator->nama_fasilitator }} @endif
                        </span>
                        @if ($s->materi->isNotEmpty())
                            <span class="w-full text-xs text-slate-400">Materi: {{ $s->materi->pluck('judul_materi')->join(', ') }}</span>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-500">Jadwal sesi belum dipublikasikan.</li>
                @endforelse
            </ul>
        </div>

        <div class="card card-pad mt-4">
            <p class="eyebrow">Kuota</p>
            @php $terisi = $kegiatan->kapasitas - $kegiatan->sisaKuota(); @endphp
            <p class="mt-1 text-lg font-semibold">{{ $terisi }} dari {{ $kegiatan->kapasitas }} pendaftar</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full bg-cyan-500" style="width: {{ min(100, round($terisi / max(1, $kegiatan->kapasitas) * 100)) }}%"></div>
            </div>
        </div>
    </div>

    <aside class="card card-pad">
        <p class="eyebrow">Formulir Pendaftaran</p>

        @guest
            <p class="mt-3 text-sm text-slate-600">Masuk atau buat akun peserta terlebih dahulu untuk mendaftar kegiatan ini.</p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary flex-1">Masuk</a>
                <a href="{{ route('registrasi') }}" class="btn btn-ghost flex-1">Daftar Akun</a>
            </div>
        @else
            @if ($sudahDaftar)
                <p class="mt-3 text-sm text-emerald-700">Anda sudah terdaftar pada kegiatan ini.</p>
                <a href="{{ route('peserta.dashboard') }}" class="btn btn-cyan mt-4 w-full">Buka Dashboard Peserta</a>
            @elseif ($kegiatan->sisaKuota() < 1)
                <p class="mt-3 text-sm text-red-700">Kuota kegiatan sudah penuh.</p>
                <button class="btn btn-primary mt-4 w-full" disabled>Kuota Penuh</button>
            @else
                <form method="POST" action="{{ route('kegiatan.daftar', $kegiatan) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label" for="nama_peserta">Nama lengkap</label>
                        <input class="input" id="nama_peserta" name="nama_peserta" required
                               value="{{ old('nama_peserta', auth()->user()->nama_pengguna) }}">
                    </div>
                    <div>
                        <label class="label" for="no_hp">Nomor HP</label>
                        <input class="input" id="no_hp" name="no_hp" value="{{ old('no_hp') }}" placeholder="08...">
                    </div>
                    <div>
                        <label class="label" for="id_mitra">Asal sekolah / instansi</label>
                        <select class="select" id="id_mitra" name="id_mitra" required>
                            <option value="">— pilih —</option>
                            @foreach ($mitra as $m)
                                <option value="{{ $m->id_mitra }}" @selected(old('id_mitra') == $m->id_mitra)>{{ $m->nama_mitra }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="peran_afiliasi">Peran</label>
                        <select class="select" id="peran_afiliasi" name="peran_afiliasi" required>
                            @foreach (['siswa', 'guru', 'staf', 'umum'] as $r)
                                <option value="{{ $r }}" @selected(old('peran_afiliasi') === $r)>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-start gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="setuju" value="1" class="mt-1" required>
                        Saya bersedia data pendaftaran diolah untuk keperluan kegiatan PkM ini.
                    </label>
                    <button class="btn btn-cyan w-full">Daftar Sekarang</button>
                </form>
            @endif
        @endguest
    </aside>
</div>
@endsection
