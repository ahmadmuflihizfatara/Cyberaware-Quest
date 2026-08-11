@extends('layouts.app')
@section('judul', 'Profil')

@section('isi')
<form method="POST" action="{{ route('peserta.profil.simpan') }}" class="card card-pad max-w-xl space-y-4">
    @csrf
    <div>
        <label class="label" for="nama_pengguna">Nama lengkap</label>
        <input class="input" id="nama_pengguna" name="nama_pengguna" required
               value="{{ old('nama_pengguna', auth()->user()->nama_pengguna) }}">
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" type="email" name="email" required
               value="{{ old('email', auth()->user()->email) }}">
    </div>
    <div>
        <label class="label" for="no_hp">Nomor HP</label>
        <input class="input" id="no_hp" name="no_hp" value="{{ old('no_hp', $peserta?->no_hp) }}">
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="password">Kata sandi baru</label>
            <input class="input" id="password" type="password" name="password" minlength="8" placeholder="kosongkan bila tidak diubah">
        </div>
        <div>
            <label class="label" for="password_confirmation">Ulangi sandi baru</label>
            <input class="input" id="password_confirmation" type="password" name="password_confirmation">
        </div>
    </div>
    <button class="btn btn-primary">Simpan perubahan</button>
</form>
@endsection
