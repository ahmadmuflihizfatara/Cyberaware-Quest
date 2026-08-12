@extends('layouts.app')
@section('judul', 'Laporan & Ekspor')

@section('isi')
<div class="animasi-masuk">
    <div class="flex flex-wrap gap-2">
        @foreach ($daftar as $kunci => $label)
            <a href="{{ route('admin.laporan', [$kunci, 'kegiatan' => $terpilih]) }}"
               class="btn btn-sm transition-transform active:scale-95 {{ $jenis === $kunci ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card card-pad mt-5" x-data="{ menyiapkan: null }">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-extrabold">{{ $def['judul'] }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    @if ($totalBaris > 500)
                        Menampilkan <strong>500</strong> dari <strong>{{ number_format($totalBaris, 0, ',', '.') }}</strong> baris total —
                        unduhan tetap memuat semuanya.
                    @else
                        <strong>{{ number_format($totalBaris, 0, ',', '.') }}</strong> baris ditemukan.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.laporan.ekspor', [$jenis, 'kegiatan' => $terpilih, 'format' => 'csv']) }}"
                   @click="menyiapkan = 'csv'; setTimeout(() => menyiapkan = null, 2000)"
                   class="btn btn-cyan">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                    </svg>
                    Unduh CSV
                </a>
                <a href="{{ route('admin.laporan.ekspor', [$jenis, 'kegiatan' => $terpilih, 'format' => 'pdf']) }}"
                   @click="menyiapkan = 'pdf'; setTimeout(() => menyiapkan = null, 2000)"
                   class="btn btn-ghost">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                    </svg>
                    Unduh PDF
                </a>
            </div>
        </div>

        <p x-show="menyiapkan" x-transition.opacity x-cloak class="mt-2 text-xs font-semibold text-emerald-600">
            Berkas <span x-text="menyiapkan === 'pdf' ? 'PDF' : 'CSV'"></span> sedang disiapkan dari data terkini&hellip;
        </p>

        <form method="GET" class="mt-4 flex flex-wrap items-end gap-3" x-data
              @change="$el.submit()">
            <div class="min-w-64">
                <label class="label" for="filter-kegiatan">Filter kegiatan</label>
                <select class="select" id="filter-kegiatan" name="kegiatan">
                    <option value="">Semua kegiatan</option>
                    @foreach ($kegiatan as $k)
                        <option value="{{ $k->id_kegiatan }}" @selected($terpilih == $k->id_kegiatan)>{{ $k->tema }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="btn btn-primary">Terapkan filter</button></noscript>
            @if ($terpilih)
                <a href="{{ route('admin.laporan', $jenis) }}" class="btn btn-ghost btn-sm">Reset filter</a>
            @endif
        </form>

        <p class="mt-3 text-xs text-slate-400">
            Data diambil langsung dari query aplikasi mengikuti filter yang dipilih — bukan berkas statis.
        </p>
    </div>

    <div class="card mt-4 tbl-wrap">
        <table class="tbl">
            <thead>
                <tr>@foreach ($def['kolom'] as $label)<th>{{ $label }}</th>@endforeach</tr>
            </thead>
            <tbody class="baris-masuk">
            @forelse ($baris as $b)
                <tr>
                    @foreach ($def['kolom'] as $kolom => $label)
                        <td>{{ $b->{$kolom} ?? '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($def['kolom']) }}" class="text-sm text-slate-500">Belum ada data untuk filter ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
