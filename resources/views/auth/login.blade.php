@extends('layouts.auth')
@section('judul', 'Masuk')

@section('isi')
<p class="eyebrow">Masuk</p>
<h1 class="mt-1 text-2xl font-bold">Selamat datang kembali</h1>
<p class="mt-2 text-sm text-slate-500">Gunakan email dan kata sandi akun Anda.</p>

<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>
    <div>
        <label class="label" for="password">Kata sandi</label>
        <input class="input" id="password" type="password" name="password" required>
    </div>
    <button class="btn btn-primary w-full">Masuk</button>
</form>

<p class="mt-5 text-sm text-slate-500">
    Belum punya akun? <a class="font-semibold text-navy-700 hover:underline" href="{{ route('registrasi') }}">Daftar sebagai peserta</a>
    · <a class="font-semibold text-navy-700 hover:underline" href="{{ route('lupa-sandi') }}">Lupa sandi</a>
</p>
@endsection
