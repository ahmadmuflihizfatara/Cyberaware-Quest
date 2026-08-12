@extends('layouts.app')
@section('judul', $kegiatan->tema)

@section('isi')
<a href="{{ route('peserta.informasi-kegiatan') }}" class="text-sm font-semibold text-slate-500 hover:text-navy-700">&larr; Semua kegiatan</a>

<div class="mt-4 max-w-2xl">
    <p class="eyebrow">Informasi Kegiatan</p>
    <h2 class="mt-1 font-display text-2xl font-extrabold leading-tight">{{ $kegiatan->tema }}</h2>
    <p class="mt-1 text-sm text-slate-500">
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

    <div class="card card-pad mt-4">
        <p class="eyebrow">Pendaftaran</p>

        @if ($sudahDaftar)
            <p class="mt-3 text-sm text-emerald-700">Anda sudah terdaftar pada kegiatan ini.</p>
            <a href="{{ route('peserta.kegiatan') }}" class="btn btn-cyan mt-4 w-full">Lihat Kegiatan Saya</a>
        @elseif ($kegiatan->sisaKuota() < 1)
            <p class="mt-3 text-sm text-red-700">Kuota kegiatan sudah penuh.</p>
            <button class="btn btn-primary mt-4 w-full" disabled>Kuota Penuh</button>
        @elseif ($kegiatan->status_kegiatan === 'dibatalkan')
            <p class="mt-3 text-sm text-red-700">Kegiatan ini dibatalkan.</p>
        @else
            <div x-cloak x-data="{
                    step: 1,
                    submitting: false,
                    langkah: ['Data Diri', 'Afiliasi', 'Konfirmasi'],
                    form: {
                        nama_peserta: @js(old('nama_peserta', auth()->user()->nama_pengguna)),
                        no_hp: @js(old('no_hp', '')),
                        id_mitra: @js((string) old('id_mitra', '')),
                        peran_afiliasi: @js(old('peran_afiliasi', 'siswa')),
                    },
                    setuju: false,
                    errors: { nama_peserta: false, id_mitra: false },
                    mitraList: [ @foreach ($mitra as $m) { id: {{ $m->id_mitra }}, nama: @js($m->nama_mitra) }, @endforeach ],
                    mitraQuery: @js(optional($mitra->firstWhere('id_mitra', old('id_mitra')))->nama_mitra ?? ''),
                    mitraOpen: false,
                    get mitraFiltered() {
                        const q = this.mitraQuery.toLowerCase();
                        return this.mitraList.filter(m => m.nama.toLowerCase().includes(q));
                    },
                    get namaMitraTerpilih() {
                        return this.mitraList.find(m => String(m.id) === this.form.id_mitra)?.nama ?? '—';
                    },
                    get validStep1() { return this.form.nama_peserta.trim().length > 0 },
                    get validStep2() { return this.form.id_mitra !== '' },
                    pilihMitra(m) {
                        this.form.id_mitra = String(m.id);
                        this.mitraQuery = m.nama;
                        this.mitraOpen = false;
                        this.errors.id_mitra = false;
                    },
                    lanjut() {
                        if (this.step === 1) {
                            this.errors.nama_peserta = !this.validStep1;
                            if (this.errors.nama_peserta) return;
                        }
                        if (this.step === 2) {
                            this.errors.id_mitra = !this.validStep2;
                            if (this.errors.id_mitra) return;
                        }
                        this.step++;
                    },
                    ke(n) { if (n < this.step) this.step = n; },
                }" class="wizard-daftar mt-4">
                <!-- indikator langkah -->
                <ol class="flex items-center">
                    <template x-for="(label, i) in langkah" :key="i">
                        <li class="flex flex-1 items-center last:flex-none">
                            <button type="button" class="flex items-center gap-2" :class="step > i + 1 ? 'cursor-pointer' : 'cursor-default'"
                                    @click="ke(i + 1)">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold transition-all duration-300"
                                      :class="[
                                          step > i + 1 ? 'bg-emerald-500 text-white' : step === i + 1 ? 'bg-cyan-500 text-navy-900 scale-110 shadow-md shadow-cyan-500/30' : 'bg-slate-100 text-slate-400',
                                      ]">
                                    <span x-show="step > i + 1" x-transition.scale>&check;</span>
                                    <span x-show="step <= i + 1" x-text="i + 1"></span>
                                </span>
                                <span class="hidden text-xs font-semibold transition-colors duration-300 sm:inline"
                                      :class="step === i + 1 ? 'text-navy-800' : 'text-slate-400'" x-text="label"></span>
                            </button>
                            <div x-show="i < langkah.length - 1" class="mx-2 h-px flex-1 transition-colors duration-500"
                                 :class="i + 1 < step ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                        </li>
                    </template>
                </ol>
                <p class="mt-2 text-xs font-semibold text-slate-500 sm:hidden">
                    Langkah <span x-text="step"></span> dari <span x-text="langkah.length"></span>
                    &middot; <span x-text="langkah[step - 1]"></span>
                </p>

                <form method="POST" action="{{ route('peserta.informasi-kegiatan.daftar', $kegiatan) }}"
                      class="mt-5 space-y-4" @submit="submitting = true">
                    @csrf

                    <div x-show="step === 1"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-x-3"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-x-0"
                         x-transition:leave-end="opacity-0 translate-x-3"
                         class="space-y-3">
                        <div>
                            <label class="label" for="nama_peserta">Nama lengkap <span class="text-red-500">*</span></label>
                            <input class="input" id="nama_peserta" name="nama_peserta" required
                                   x-model="form.nama_peserta" @blur="errors.nama_peserta = !validStep1"
                                   @input="errors.nama_peserta = false" :class="errors.nama_peserta && 'field-error'">
                            <p x-show="errors.nama_peserta" x-transition.opacity class="mt-1 text-xs font-medium text-red-600">
                                Nama lengkap wajib diisi.
                            </p>
                        </div>
                        <div>
                            <label class="label" for="no_hp">Nomor HP <span class="font-normal text-slate-400">(opsional)</span></label>
                            <input class="input" id="no_hp" name="no_hp" placeholder="08..." inputmode="numeric"
                                   x-model="form.no_hp" @input="form.no_hp = form.no_hp.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    <div x-show="step === 2"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-x-3"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-x-0"
                         x-transition:leave-end="opacity-0 translate-x-3"
                         class="space-y-3">
                        <div class="relative" @click.outside="mitraOpen = false">
                            <label class="label" for="id_mitra_cari">Asal sekolah / instansi <span class="text-red-500">*</span></label>
                            <input type="text" class="input" id="id_mitra_cari" autocomplete="off"
                                   placeholder="Ketik untuk mencari sekolah/instansi…"
                                   x-model="mitraQuery"
                                   :class="errors.id_mitra && 'field-error'"
                                   @focus="mitraOpen = true"
                                   @input="mitraOpen = true; form.id_mitra = ''; errors.id_mitra = false"
                                   @blur="setTimeout(() => { if (!form.id_mitra) errors.id_mitra = true }, 150)">
                            <input type="hidden" name="id_mitra" :value="form.id_mitra">

                            <div x-show="mitraOpen" x-transition.opacity.duration.150ms
                                 class="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-line bg-white shadow-lg">
                                <ul class="max-h-56 overflow-y-auto">
                                    <template x-for="m in mitraFiltered" :key="m.id">
                                        <li>
                                            <button type="button"
                                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-cyan-50"
                                                    @click="pilihMitra(m)">
                                                <span x-text="m.nama"></span>
                                            </button>
                                        </li>
                                    </template>
                                    <li x-show="mitraFiltered.length === 0" class="px-3 py-2 text-sm text-slate-400">
                                        Tidak ditemukan.
                                    </li>
                                </ul>
                            </div>
                            <p x-show="errors.id_mitra" x-transition.opacity class="mt-1 text-xs font-medium text-red-600">
                                Pilih sekolah/instansi dari daftar.
                            </p>
                        </div>
                        <div>
                            <label class="label" for="peran_afiliasi">Peran <span class="text-red-500">*</span></label>
                            <select class="select" id="peran_afiliasi" name="peran_afiliasi" required x-model="form.peran_afiliasi">
                                @foreach (['siswa', 'guru', 'staf', 'umum'] as $r)
                                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div x-show="step === 3"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-x-3"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-x-0"
                         x-transition:leave-end="opacity-0 translate-x-3"
                         class="space-y-4">
                        <dl class="divide-y divide-slate-100 rounded-lg border border-line text-sm">
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-slate-500">Nama</dt>
                                <div class="flex items-center gap-2">
                                    <dd class="font-semibold" x-text="form.nama_peserta"></dd>
                                    <button type="button" class="text-xs font-semibold text-cyan-600 hover:underline" @click="ke(1)">Ubah</button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-slate-500">Nomor HP</dt>
                                <div class="flex items-center gap-2">
                                    <dd class="font-semibold" x-text="form.no_hp || '—'"></dd>
                                    <button type="button" class="text-xs font-semibold text-cyan-600 hover:underline" @click="ke(1)">Ubah</button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-slate-500">Asal sekolah</dt>
                                <div class="flex items-center gap-2">
                                    <dd class="font-semibold" x-text="namaMitraTerpilih"></dd>
                                    <button type="button" class="text-xs font-semibold text-cyan-600 hover:underline" @click="ke(2)">Ubah</button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-slate-500">Peran</dt>
                                <div class="flex items-center gap-2">
                                    <dd class="font-semibold capitalize" x-text="form.peran_afiliasi"></dd>
                                    <button type="button" class="text-xs font-semibold text-cyan-600 hover:underline" @click="ke(2)">Ubah</button>
                                </div>
                            </div>
                        </dl>

                        <label class="flex items-start gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="setuju" value="1" class="mt-1" required x-model="setuju">
                            <span>
                                Saya bersedia data pendaftaran diolah untuk keperluan kegiatan PkM ini, sesuai
                                <a href="{{ route('kebijakan-privasi') }}" target="_blank" rel="noopener"
                                   class="font-semibold text-navy-700 hover:underline">kebijakan privasi</a>.
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <button type="button" x-show="step > 1" x-transition.opacity.duration.200ms
                                @click="step--" class="btn btn-ghost">Kembali</button>

                        <button type="button" x-show="step < 3" x-transition.opacity.duration.200ms
                                @click="lanjut()" class="btn btn-primary ml-auto">
                            Lanjut
                        </button>

                        <button type="submit" x-show="step === 3" x-transition.opacity.duration.200ms
                                :disabled="!setuju || submitting" class="btn btn-cyan ml-auto">
                            <span x-show="!submitting" class="inline-flex items-center gap-2">Daftar Sekarang</span>
                            <span x-show="submitting" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Memproses&hellip;
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
