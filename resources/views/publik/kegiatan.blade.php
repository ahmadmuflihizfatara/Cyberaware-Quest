@extends('layouts.publik')
@section('judul', 'Kegiatan')

@section('isi')
<p class="eyebrow">Area Publik</p>
<h1 class="text-3xl font-bold">Kegiatan Terbuka</h1>

<form method="GET" class="card card-pad mt-6 grid gap-3 sm:grid-cols-[2fr_1fr_auto]">
    <div>
        <label class="label" for="cari">Cari tema</label>
        <input class="input" id="cari" name="cari" value="{{ request('cari') }}" placeholder="mis. phishing">
    </div>
    <div>
        <label class="label" for="mode">Mode pelaksanaan</label>
        <select class="select" id="mode" name="mode">
            <option value="">Semua</option>
            @foreach (['luring', 'daring', 'hybrid'] as $m)
                <option value="{{ $m }}" @selected(request('mode') === $m)>{{ ucfirst($m) }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end"><button class="btn btn-primary w-full sm:w-auto">Terapkan</button></div>
</form>

<div class="mt-6 grid gap-4 md:grid-cols-3">
    @forelse ($kegiatan as $k)
        @include('publik.partials.kartu-kegiatan', ['k' => $k])
    @empty
        <p class="text-sm text-slate-500">Tidak ada kegiatan yang cocok dengan filter.</p>
    @endforelse
</div>

<div class="mt-6">{{ $kegiatan->links() }}</div>
@endsection
