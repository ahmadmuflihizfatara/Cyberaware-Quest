@extends('layouts.app')
@section('judul', 'Profil')

@section('isi')
<form method="POST" action="{{ route('peserta.profil.simpan') }}" enctype="multipart/form-data" class="card card-pad max-w-xl space-y-4">
    @csrf
    
    @if ($peserta?->foto_profil)
        <div class="mb-4">
            <img src="{{ asset('storage/' . $peserta->foto_profil) }}" alt="Foto Profil" class="w-24 h-24 object-cover rounded-full ring-2 ring-cyan-500">
        </div>
    @endif
    
    <div>
        <label class="label" for="foto_profil">Unggah Foto Profil Baru</label>
        <input class="input" id="foto_profil" type="file" name="foto_profil" accept="image/*">
    </div>

    <div>
        <label class="label" for="nama_pengguna">Nama lengkap</label>
        <input class="input" id="nama_pengguna" name="nama_pengguna" required
               value="{{ old('nama_pengguna', auth()->user()->nama_pengguna) }}">
    </div>
    
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="email">Email</label>
            <input class="input" id="email" type="email" name="email" required
                   value="{{ old('email', auth()->user()->email) }}">
        </div>
        <div>
            <label class="label" for="no_hp">Nomor HP</label>
            <input class="input" id="no_hp" name="no_hp" value="{{ old('no_hp', $peserta?->no_hp) }}">
        </div>
    </div>
    
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="no_ktp">Nomor KTP / NIK</label>
            <input class="input" id="no_ktp" name="no_ktp" value="{{ old('no_ktp', $peserta?->no_ktp) }}">
        </div>
        <div>
            <label class="label" for="npm">NPM / NIS</label>
            <input class="input" id="npm" name="npm" value="{{ old('npm', $peserta?->npm) }}">
        </div>
    </div>

    <div>
        <label class="label" for="asal_sekolah">Asal Sekolah / Kampus / Instansi</label>
        <input class="input" id="asal_sekolah" name="asal_sekolah" value="{{ old('asal_sekolah', $peserta?->asal_sekolah) }}">
    </div>
    
    <div>
        <label class="label" for="alamat_domisili">Alamat Domisili</label>
        <textarea class="input" id="alamat_domisili" name="alamat_domisili" rows="3">{{ old('alamat_domisili', $peserta?->alamat_domisili) }}</textarea>
    </div>

    <hr class="my-4 border-slate-200">

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
