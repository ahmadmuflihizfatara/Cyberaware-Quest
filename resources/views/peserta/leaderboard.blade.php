@extends('layouts.app', ['aktifPendaftaran' => $p])
@section('judul', 'Leaderboard')

@section('isi')
<p class="eyebrow">Kegiatan</p>
<h2 class="mt-1 font-display text-2xl font-extrabold">{{ $p->kegiatan->tema }}</h2>
<p class="mt-1 text-sm text-slate-500">Peringkat berdasarkan poin gamifikasi yang diperoleh, bukan skor tes.</p>

@include('peserta.partials.leaderboard', ['leaderboard' => $leaderboard, 'p' => $p])
@endsection
