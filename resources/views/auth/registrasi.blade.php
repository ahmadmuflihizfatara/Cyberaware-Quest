@extends('layouts.auth')
@section('judul', 'Registrasi')

@section('isi')
<p class="eyebrow">Registrasi</p>
<h1 class="mt-1 text-2xl font-bold">Buat akun peserta</h1>
<p class="mt-2 text-sm text-slate-500">
    Akun baru otomatis mendapat peran <strong>peserta</strong>. Peran fasilitator dan admin diberikan panitia.
</p>

<form method="POST" action="{{ route('registrasi') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label class="label" for="nama_pengguna">Nama lengkap</label>
        <input class="input" id="nama_pengguna" name="nama_pengguna" value="{{ old('nama_pengguna') }}" required autofocus>
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div>
        <label class="label" for="no_hp">Nomor HP <span class="font-normal text-slate-400">(opsional)</span></label>
        <input class="input" id="no_hp" name="no_hp" value="{{ old('no_hp') }}">
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="password">Kata sandi</label>
            <input class="input" id="password" type="password" name="password" required minlength="8">
        </div>
        <div>
            <label class="label" for="password_confirmation">Ulangi sandi</label>
            <input class="input" id="password_confirmation" type="password" name="password_confirmation" required>
        </div>
    </div>
    <button class="btn btn-cyan w-full">Daftar</button>
</form>

<p class="mt-5 text-sm text-slate-500">
    Sudah punya akun? <a class="font-semibold text-navy-700 hover:underline" href="{{ route('login') }}">Masuk</a>
</p>
@endsection
