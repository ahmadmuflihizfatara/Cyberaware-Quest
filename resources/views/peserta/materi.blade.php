@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Materi Sesi')

@section('isi')
<div class="space-y-4">
    @forelse ($p->kegiatan->sesi as $s)
        @php $sudahHadir = in_array($s->id_sesi, $hadir); @endphp
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-3">
                <span class="chip {{ $sudahHadir ? 'chip-ok' : 'chip-off' }}">{{ $sudahHadir ? 'Hadir' : 'Belum check-in' }}</span>
                <h3 class="text-lg font-bold">Sesi {{ $s->urutan_sesi }} · {{ $s->judul_sesi }}</h3>
                <span class="text-sm text-slate-500">
                    {{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}
                    @if ($s->fasilitator) · {{ $s->fasilitator->nama_fasilitator }} @endif
                </span>
            </div>

            @if ($sudahHadir)
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @forelse ($s->materi as $m)
                        <div class="rounded-xl border border-line p-3">
                            <p class="font-semibold">{{ $m->judul_materi }}</p>
                            <p class="mt-0.5 text-xs uppercase tracking-wide text-cyan-600">{{ str_replace('_', ' ', $m->kategori) }}</p>
                            <p class="mt-1.5 text-sm text-slate-500">{{ $m->deskripsi }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Materi belum ditautkan pada sesi ini.</p>
                    @endforelse
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">
                    Materi terbuka setelah kehadiran sesi tercatat.
                    <a class="font-semibold text-navy-700 hover:underline" href="{{ route('peserta.checkin', $p) }}">Check-in sekarang &rarr;</a>
                </p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada sesi pada kegiatan ini.</p>
    @endforelse
</div>
@endsection
