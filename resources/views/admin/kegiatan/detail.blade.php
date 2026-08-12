@extends('layouts.app')
@section('judul', $k->tema)

@section('isi')
<div class="card card-pad">
    <p class="eyebrow">{{ $k->program->nama_program }}</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold">{{ $k->tema }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        {{ $k->sekolah?->mitra?->nama_mitra }}
        @if ($k->lokasi) · {{ $k->lokasi->nama_lokasi }} @endif
        · {{ ucfirst($k->mode_pelaksanaan) }} · {{ $k->tanggal_mulai?->translatedFormat('d F Y') }}
        · kapasitas {{ $k->kapasitas }} · status {{ $k->status_kegiatan }}
    </p>
</div>

{{-- ------------------------------------------------------------------ SESI --}}
<h3 class="mt-7 text-lg font-bold">Sesi &amp; materi</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>#</th><th>Sesi</th><th>Fasilitator</th><th>Tanggal</th><th>Jam</th><th>Hadir</th><th>Token</th><th></th></tr></thead>
        <tbody>
        @forelse ($k->sesi as $s)
            <tr>
                <td>{{ $s->urutan_sesi }}</td>
                <td class="font-semibold">{{ $s->judul_sesi }}</td>
                <td>{{ $s->fasilitator?->nama_fasilitator }}</td>
                <td>{{ $s->tanggal_sesi?->format('d/m/Y') }}</td>
                <td>{{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}</td>
                <td>{{ $rekapHadir[$s->id_sesi] ?? 0 }}</td>
                <td>
                    @php $tk = $s->tokenAktif(); @endphp
                    @if ($tk)
                        <span class="font-mono bg-slate-100 px-2 py-1 rounded">{{ $tk->token }}</span>
                    @else
                        <form method="POST" action="{{ route('admin.sesi.token.buat', $s) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm py-1 h-auto text-xs">Buat Token</button>
                        </form>
                    @endif
                </td>
                <td class="text-right">
                    <form method="POST" action="{{ route('admin.sesi.hapus', $s) }}" onsubmit="return confirm('Hapus sesi ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm text-xs py-1 h-auto">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-sm text-slate-500">Belum ada sesi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<form method="POST" action="{{ route('admin.kegiatan.sesi', $k) }}" class="card card-pad mt-4 grid gap-3 md:grid-cols-3">
    @csrf
    <div class="md:col-span-2">
        <label class="label">Judul sesi</label>
        <input class="input" name="judul_sesi" required>
    </div>
    <div>
        <label class="label">Urutan</label>
        <input class="input" type="number" name="urutan_sesi" min="1" value="{{ $k->sesi->count() + 1 }}" required>
    </div>
    <div>
        <label class="label">Fasilitator</label>
        <select class="select" name="id_fasilitator" required>
            @foreach ($fasilitator as $f)
                <option value="{{ $f->id_fasilitator }}">{{ $f->nama_fasilitator }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Tanggal</label>
        <input class="input" type="date" name="tanggal_sesi" value="{{ $k->tanggal_mulai?->format('Y-m-d') }}" required>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Jam mulai</label><input class="input" type="time" name="jam_mulai" value="08:00" required></div>
        <div><label class="label">Jam selesai</label><input class="input" type="time" name="jam_selesai" value="09:30" required></div>
    </div>
    <div class="md:col-span-3">
        <label class="label">Materi (boleh lebih dari satu)</label>
        <div class="grid gap-1.5 sm:grid-cols-3">
            @foreach ($materi as $m)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="materi[]" value="{{ $m->id_materi }}"> {{ $m->judul_materi }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="md:col-span-3"><button class="btn btn-primary">Tambah sesi</button></div>
</form>

{{-- ---------------------------------------------------------- FASILITATOR --}}
<h3 class="mt-8 text-lg font-bold">Penugasan fasilitator</h3>
<div class="card card-pad mt-3">
    <div class="flex flex-wrap gap-2">
        @forelse ($k->fasilitator as $f)
            <span class="chip chip-info">
                {{ $f->nama_fasilitator }} · {{ $f->pivot->peran_penugasan }}
                <form method="POST" action="{{ route('admin.kegiatan.fasilitator.lepas', [$k, $f]) }}" class="inline">
                    @csrf @method('DELETE')
                    <button class="ml-1 font-bold" title="Lepas penugasan">×</button>
                </form>
            </span>
        @empty
            <p class="text-sm text-slate-500">Belum ada fasilitator ditugaskan.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.kegiatan.fasilitator', $k) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
        @csrf
        <div>
            <label class="label">Fasilitator</label>
            <select class="select" name="id_fasilitator" required>
                @foreach ($fasilitator as $f)
                    <option value="{{ $f->id_fasilitator }}">{{ $f->nama_fasilitator }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Peran penugasan</label>
            <input class="input" name="peran_penugasan" value="pemateri" required>
        </div>
        <div class="flex items-end"><button class="btn btn-primary w-full">Tugaskan</button></div>
    </form>
</div>

{{-- --------------------------------------------------------- PELAKSANAAN --}}
<h3 class="mt-8 text-lg font-bold">Pelaksanaan instrumen</h3>
<p class="text-xs text-slate-400">Pre-test dan post-test wajib memakai versi instrumen yang sama (aturan bisnis #5).</p>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead><tr><th>Fase</th><th>Instrumen</th><th>Versi</th><th>Dibuka</th><th>Ditutup</th><th>Tampilkan Hasil</th></tr></thead>
        <tbody>
        @forelse ($k->pelaksanaan as $pl)
            <tr>
                <td><span class="chip chip-info">{{ $pl->fase }}</span></td>
                <td class="font-semibold">{{ $pl->versi->instrumen->nama_instrumen }}</td>
                <td>v{{ $pl->versi->nomor_versi }} · {{ $pl->versi->status_versi }}</td>
                <td>{{ $pl->dibuka_pada?->format('d/m/Y H:i') ?? 'segera' }}</td>
                <td>{{ $pl->ditutup_pada?->format('d/m/Y H:i') ?? 'tanpa batas' }}</td>
                <td>
                    @if (in_array($pl->fase, ['demografi', 'pretest', 'posttest']))
                        <form method="POST" action="{{ route('admin.kegiatan.hasil.toggle', [$k, $pl->fase]) }}">
                            @csrf
                            <button class="btn btn-sm {{ $pl->tampilkan_hasil ? 'btn-outline' : 'btn-primary' }}">
                                @if ($pl->fase === 'demografi')
                                    {{ $pl->tampilkan_hasil ? 'Tutup Akses Pre-test' : 'Lanjutkan Pre-test' }}
                                @else
                                    {{ $pl->tampilkan_hasil ? 'Sembunyikan' : 'Tampilkan' }}
                                @endif
                            </button>
                        </form>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-sm text-slate-500">Belum ada fase ditetapkan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<form method="POST" action="{{ route('admin.kegiatan.pelaksanaan', $k) }}" class="card card-pad mt-4 grid gap-3 sm:grid-cols-4">
    @csrf
    <div>
        <label class="label">Fase</label>
        <select class="select" name="fase" required>
            @foreach (['demografi', 'pretest', 'posttest'] as $f)
                <option value="{{ $f }}">{{ $f }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Versi instrumen</label>
        <select class="select" name="id_versi" required>
            @foreach ($versi as $v)
                <option value="{{ $v->id_versi }}">{{ $v->instrumen->nama_instrumen }} v{{ $v->nomor_versi }} ({{ $v->instrumen->tipe_instrumen }})</option>
            @endforeach
        </select>
    </div>
    <div><label class="label">Dibuka</label><input class="input" type="datetime-local" name="dibuka_pada"></div>
    <div><label class="label">Ditutup</label><input class="input" type="datetime-local" name="ditutup_pada"></div>
    <div class="sm:col-span-4"><button class="btn btn-primary">Tetapkan fase</button></div>
</form>

{{-- ------------------------------------------------------------ EVALUASI --}}
<h3 class="mt-8 text-lg font-bold">Kuesioner penyelenggaraan</h3>
<form method="POST" action="{{ route('admin.kegiatan.evaluasi', $k) }}" class="card card-pad mt-3 grid gap-3 sm:grid-cols-4">
    @csrf
    <div class="sm:col-span-2">
        <label class="label">Versi instrumen kuesioner</label>
        <select class="select" name="id_versi" required>
            @foreach ($versi->where('instrumen.tipe_instrumen', 'kuesioner') as $v)
                <option value="{{ $v->id_versi }}" @selected($k->konfigurasiEvaluasi?->id_versi === $v->id_versi)>
                    {{ $v->instrumen->nama_instrumen }} v{{ $v->nomor_versi }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Mode evaluasi</label>
        <select class="select" name="mode_evaluasi" required>
            @foreach (['identitas', 'anonim'] as $m)
                <option value="{{ $m }}" @selected($k->konfigurasiEvaluasi?->mode_evaluasi === $m)>{{ $m }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end"><button class="btn btn-primary w-full">Simpan konfigurasi</button></div>
</form>

@if ($butirKuesioner->isNotEmpty())
    <form method="POST" action="{{ route('admin.kegiatan.indikator', $k) }}" class="card card-pad mt-4">
        @csrf
        <h4 class="font-bold">Pemetaan indikator evaluasi</h4>
        <p class="mt-1 text-xs text-slate-400">Setiap butir kuesioner dipetakan ke aspek yang dinilai.</p>
        <div class="mt-3 space-y-2">
            @foreach ($butirKuesioner as $b)
                <div class="grid items-center gap-3 sm:grid-cols-[1fr_14rem]">
                    <p class="text-sm">{{ $b->nomor_urut }}. {{ $b->teks_butir }}</p>
                    <select class="select" name="indikator[{{ $b->id_butir }}]">
                        <option value="">— tidak dipetakan —</option>
                        @foreach (['materi', 'fasilitator', 'metode', 'fasilitas_platform', 'manfaat', 'kepuasan', 'saran'] as $a)
                            <option value="{{ $a }}" @selected($b->indikator?->aspek_dinilai === $a)>{{ str_replace('_', ' ', $a) }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
        <button class="btn btn-primary mt-4">Simpan pemetaan</button>
    </form>
@endif

{{-- --------------------------------------------------------- PENDAFTARAN --}}
<h3 class="mt-8 text-lg font-bold">Progres Peserta</h3>
<div class="card mt-3 tbl-wrap">
    <table class="tbl">
        <thead>
            <tr>
                <th>Peserta & Afiliasi</th>
                <th class="text-center">Demografi</th>
                <th class="text-center">Pre-test</th>
                <th class="text-center">Post-test</th>
                <th class="text-center">Kuesioner</th>
                <th class="text-center">Sertifikat</th>
                <th class="text-right">Status Pendaftaran</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($progres as $p)
            <tr>
                <td>
                    <div class="font-semibold">{{ $p->nama_peserta }}</div>
                    <div class="text-xs text-slate-500">{{ $p->nama_mitra ?? 'Umum' }}</div>
                </td>
                <td class="text-center">
                    {!! $responsDemografi->contains($p->id_pendaftaran) ? '<span class="text-green-600 font-bold">✅</span>' : '<span class="text-slate-300">❌</span>' !!}
                </td>
                <td class="text-center font-mono">{{ $p->skor_pretest ?? '—' }}</td>
                <td class="text-center font-mono">{{ $p->skor_posttest ?? '—' }}</td>
                <td class="text-center">
                    {!! $responsKuesioner->contains($p->id_pendaftaran) ? '<span class="text-green-600 font-bold">✅</span>' : '<span class="text-slate-300">❌</span>' !!}
                </td>
                <td class="text-center">
                    {!! $sertifikatTercetak->contains($p->id_pendaftaran) ? '<span class="chip chip-success">Terbit</span>' : '—' !!}
                </td>
                <td class="text-right">
                    <form method="POST" action="{{ route('admin.pendaftaran.status', $p->id_pendaftaran) }}" class="flex justify-end gap-2">
                        @csrf @method('PUT')
                        <select class="select w-32" name="status_pendaftaran">
                            @foreach (['terdaftar', 'hadir', 'tidak_hadir', 'dibatalkan'] as $st)
                                <option value="{{ $st }}" @selected($p->status_pendaftaran === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-ghost btn-sm py-1 h-auto text-xs">Ubah</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-sm text-slate-500">Belum ada pendaftar.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="flex items-center gap-2 mt-4">
    <a href="{{ route('admin.kegiatan.demografi', $k) }}" class="btn btn-outline">
        Lihat Data Demografi
    </a>
    <a href="{{ route('admin.laporan.ekspor', ['kehadiran', 'kegiatan' => $k->id_kegiatan]) }}" class="btn btn-ghost">
        Ekspor rekap kehadiran (CSV)
    </a>
    <form method="POST" action="{{ route('admin.kegiatan.sertifikat.terbitkan', $k) }}">
        @csrf
        <button class="btn btn-primary" onclick="return confirm('Terbitkan e-sertifikat untuk semua peserta yang telah menyelesaikan kuesioner?')">
            Terbitkan E-Sertifikat
        </button>
    </form>
</div>
@endsection
