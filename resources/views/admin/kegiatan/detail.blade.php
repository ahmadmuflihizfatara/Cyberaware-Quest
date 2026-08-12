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
        <thead><tr><th>#</th><th>Sesi</th><th>Fasilitator</th><th>Tanggal</th><th>Jam</th><th>Materi</th><th>Hadir</th><th></th></tr></thead>
        <tbody>
        @forelse ($k->sesi as $s)
            <tr>
                <td>{{ $s->urutan_sesi }}</td>
                <td class="font-semibold">{{ $s->judul_sesi }}</td>
                <td>{{ $s->fasilitator?->nama_fasilitator }}</td>
                <td>{{ $s->tanggal_sesi?->format('d/m/Y') }}</td>
                <td>{{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}</td>
                <td class="text-slate-500">{{ $s->materi->pluck('judul_materi')->join(', ') ?: '—' }}</td>
                <td>{{ $rekapHadir[$s->id_sesi] ?? 0 }}</td>
                <td class="text-right">
                    <form method="POST" action="{{ route('admin.sesi.hapus', $s) }}" onsubmit="return confirm('Hapus sesi ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Hapus</button>
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
        <thead><tr><th>Fase</th><th>Instrumen</th><th>Versi</th><th>Dibuka</th><th>Ditutup</th></tr></thead>
        <tbody>
        @forelse ($k->pelaksanaan as $pl)
            <tr>
                <td><span class="chip chip-info">{{ $pl->fase }}</span></td>
                <td class="font-semibold">{{ $pl->versi->instrumen->nama_instrumen }}</td>
                <td>v{{ $pl->versi->nomor_versi }} · {{ $pl->versi->status_versi }}</td>
                <td>{{ $pl->dibuka_pada?->format('d/m/Y H:i') ?? 'segera' }}</td>
                <td>{{ $pl->ditutup_pada?->format('d/m/Y H:i') ?? 'tanpa batas' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-sm text-slate-500">Belum ada fase ditetapkan.</td></tr>
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
@php
    $warnaStatus = ['terdaftar' => 'chip-off', 'hadir' => 'chip-ok', 'tidak_hadir' => 'chip-warn', 'dibatalkan' => 'chip-bad'];
    $urlStatusTemplate = route('admin.pendaftaran.status', ['pendaftaran' => '__ID__']);
@endphp

<h3 id="pendaftar" class="mt-8 text-lg font-bold">
    Pendaftar <span class="text-sm font-normal text-slate-400">({{ $pendaftaran->total() }})</span>
</h3>

<div x-data="{
        terpilih: [],
        idHalaman: @js($pendaftaran->pluck('id_pendaftaran')),
        statusMassal: 'hadir',
        memproses: false,
        pesan: null,
        semuaTerpilih() { return this.idHalaman.length > 0 && this.terpilih.length === this.idHalaman.length; },
        toggleSemua(cek) { this.terpilih = cek ? [...this.idHalaman] : []; },
        warna: { terdaftar: 'chip-off', hadir: 'chip-ok', tidak_hadir: 'chip-warn', dibatalkan: 'chip-bad' },
        async ubahStatus(id, status, baris) {
            const chip = baris.querySelector('.status-chip');
            const url = @js($urlStatusTemplate).replace('__ID__', id);
            try {
                const r = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ status_pendaftaran: status }),
                });
                const data = await r.json();
                if (data.sukses) {
                    chip.className = 'chip status-chip ' + this.warna[status];
                    chip.textContent = status;
                    baris.classList.remove('tr-flash');
                    void baris.offsetWidth;
                    baris.classList.add('tr-flash');
                }
            } catch (e) {
                alert('Gagal mengubah status. Periksa koneksi lalu coba lagi.');
            }
        },
        async terapkanMassal() {
            if (this.terpilih.length === 0) return;
            this.memproses = true;
            try {
                const r = await fetch(@js(route('admin.kegiatan.pendaftaran.massal', $k)), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ id_pendaftaran: this.terpilih, status_pendaftaran: this.statusMassal }),
                });
                const data = await r.json();
                if (data.sukses) window.location.reload();
            } finally {
                this.memproses = false;
            }
        },
    }" class="animasi-masuk mt-3">

    <form method="GET" action="{{ route('admin.kegiatan.show', $k) }}#pendaftar" class="card card-pad flex flex-wrap items-end gap-3">
        <div class="min-w-48 flex-1">
            <label class="label" for="cari-pendaftar">Cari nama peserta</label>
            <input type="text" class="input" id="cari-pendaftar" name="cari" value="{{ request('cari') }}" placeholder="mis. Nadia">
        </div>
        <div class="w-44">
            <label class="label" for="status-pendaftar">Status</label>
            <select class="select" id="status-pendaftar" name="status">
                <option value="">Semua status</option>
                @foreach (['terdaftar', 'hadir', 'tidak_hadir', 'dibatalkan'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary">Terapkan</button>
        @if (request()->hasAny(['cari', 'status']))
            <a href="{{ route('admin.kegiatan.show', $k) }}#pendaftar" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div x-show="terpilih.length > 0" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="card card-pad mt-3 flex flex-wrap items-center gap-3 border-cyan-200 bg-cyan-50/50">
        <span class="text-sm font-semibold text-navy-800"><span x-text="terpilih.length"></span> dipilih</span>
        <select class="select w-40" x-model="statusMassal">
            @foreach (['terdaftar', 'hadir', 'tidak_hadir', 'dibatalkan'] as $st)
                <option value="{{ $st }}">{{ $st }}</option>
            @endforeach
        </select>
        <button type="button" class="btn btn-cyan btn-sm" :disabled="memproses" @click="terapkanMassal()">
            <span x-show="!memproses">Terapkan ke terpilih</span>
            <span x-show="memproses">Memproses&hellip;</span>
        </button>
        <button type="button" class="btn btn-ghost btn-sm" @click="terpilih = []">Batal</button>
    </div>

    <div class="card mt-3 tbl-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th class="w-8">
                        <input type="checkbox" :checked="semuaTerpilih()" @change="toggleSemua($event.target.checked)">
                    </th>
                    <th>Peserta</th>
                    <th>Afiliasi</th>
                    <th>Terdaftar</th>
                    <th>Progres</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="baris-masuk">
            @forelse ($pendaftaran as $p)
                @php
                    $fase = $faseSelesai[$p->id_pendaftaran] ?? [];
                @endphp
                <tr>
                    <td><input type="checkbox" value="{{ $p->id_pendaftaran }}" x-model="terpilih"></td>
                    <td class="font-semibold">{{ $p->peserta->nama_peserta }}</td>
                    <td>{{ $p->afiliasi?->mitra?->nama_mitra }} ({{ $p->afiliasi?->peran_afiliasi }})</td>
                    <td>
                        {{ $p->tanggal_daftar?->format('d/m/Y H:i') }}
                        <span class="block text-xs text-slate-400">{{ $p->tanggal_daftar?->diffForHumans() }}</span>
                    </td>
                    <td>
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="chip {{ $p->kehadiran_count > 0 ? 'chip-ok' : 'chip-off' }}">
                                Hadir {{ $p->kehadiran_count }}/{{ $totalSesi }}
                            </span>
                            <span class="chip {{ in_array('pretest', $fase) ? 'chip-ok' : 'chip-off' }}">Pre</span>
                            <span class="chip {{ in_array('posttest', $fase) ? 'chip-ok' : 'chip-off' }}">Post</span>
                            <span class="chip {{ in_array('kuesioner', $fase) ? 'chip-ok' : 'chip-off' }}">Kues</span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="chip status-chip {{ $warnaStatus[$p->status_pendaftaran] }}">{{ $p->status_pendaftaran }}</span>
                            <select class="select select-sm w-32"
                                    @change="ubahStatus({{ $p->id_pendaftaran }}, $event.target.value, $event.target.closest('tr'))">
                                <option value="" selected disabled>Ubah…</option>
                                @foreach (['terdaftar', 'hadir', 'tidak_hadir', 'dibatalkan'] as $st)
                                    <option value="{{ $st }}">{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-sm text-slate-500">Belum ada pendaftar yang cocok dengan filter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pendaftaran->links() }}</div>
</div>

<a href="{{ route('admin.laporan.ekspor', ['kehadiran', 'kegiatan' => $k->id_kegiatan]) }}" class="btn btn-ghost mt-4">
    Ekspor rekap kehadiran (CSV)
</a>
@endsection
