@extends('layouts.auth')
@section('judul', 'Lupa Sandi')

@section('isi')
<p class="eyebrow">Bantuan Akun</p>
<h1 class="mt-1 text-2xl font-bold">Lupa kata sandi</h1>
<p class="mt-3 text-sm leading-relaxed text-slate-600">
    Pengaturan ulang kata sandi dilakukan panitia melalui menu <strong>Admin → Pengguna</strong>.
    Hubungi panitia kegiatan dengan menyebutkan email akun Anda, lalu masuk kembali dengan sandi baru.
</p>
<a href="{{ route('login') }}" class="btn btn-primary mt-6 w-full">Kembali ke halaman masuk</a>
@endsection
