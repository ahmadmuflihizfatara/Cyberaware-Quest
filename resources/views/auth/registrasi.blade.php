@extends('layouts.auth')
@section('judul', 'Registrasi')

@section('panel')
    <p class="eyebrow text-cyan-500">Registrasi</p>
    <h2 class="mt-2 font-display text-3xl font-extrabold leading-tight">
        Satu akun, langsung siap ikut kegiatan.
    </h2>
    <p class="mt-4 max-w-md text-slate-300 leading-relaxed">
        Buat akun sekali untuk mendaftar kegiatan, memantau progres enam tahap, mengumpulkan
        poin dan badge, sampai mengunduh sertifikat penyelesaian.
    </p>
@endsection

@section('isi')
<div class="animasi-masuk" x-data="{
        password: '',
        passwordConfirm: '',
        showPassword: false,
        showConfirm: false,
        submitting: false,
        get skorSandi() {
            const s = this.password;
            let skor = 0;
            if (s.length >= 8) skor++;
            if (/[a-z]/.test(s) && /[A-Z]/.test(s)) skor++;
            if (/[0-9]/.test(s)) skor++;
            if (/[^a-zA-Z0-9]/.test(s)) skor++;
            return s.length ? skor : -1;
        },
        get labelSandi() {
            return ['Terlalu pendek', 'Lemah', 'Sedang', 'Kuat', 'Sangat kuat'][this.skorSandi] ?? '';
        },
        get warnaSandi() {
            return ['bg-red-400', 'bg-red-400', 'bg-amber-400', 'bg-emerald-400', 'bg-emerald-500'][this.skorSandi] ?? 'bg-slate-200';
        },
        get sandiCocok() { return this.passwordConfirm.length > 0 && this.passwordConfirm === this.password },
        get sandiBeda() { return this.passwordConfirm.length > 0 && this.passwordConfirm !== this.password },
    }">
    <p class="eyebrow">Registrasi</p>
    <h1 class="mt-1 text-2xl font-bold">Buat akun peserta</h1>
    <p class="mt-2 text-sm text-slate-500">
        Akun baru otomatis mendapat peran <strong>peserta</strong>. Peran fasilitator dan admin diberikan panitia.
    </p>

    <form method="POST" action="{{ route('registrasi') }}" class="field-masuk mt-6 space-y-4" @submit="submitting = true">
        @csrf
        <div>
            <label class="label" for="nama_pengguna">Nama lengkap <span class="text-red-500">*</span></label>
            <div class="relative">
                <svg class="ikon-field h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <input class="input input-ikon" id="nama_pengguna" name="nama_pengguna"
                       value="{{ old('nama_pengguna') }}" required autofocus>
            </div>
        </div>

        <div>
            <label class="label" for="email">Email <span class="text-red-500">*</span></label>
            <div class="relative">
                <svg class="ikon-field h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <input class="input input-ikon" id="email" type="email" name="email"
                       value="{{ old('email') }}" required>
            </div>
        </div>

        <div>
            <label class="label" for="no_hp">Nomor HP <span class="font-normal text-slate-400">(opsional)</span></label>
            <div class="relative">
                <svg class="ikon-field h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.04 11.04 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <input class="input input-ikon" id="no_hp" name="no_hp" inputmode="numeric" placeholder="08..."
                       value="{{ old('no_hp') }}" @input="$el.value = $el.value.replace(/[^0-9]/g, '')">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="password">Kata sandi <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="ikon-field h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input class="input input-ikon pr-10" id="password" name="password" required minlength="8"
                           :type="showPassword ? 'text' : 'password'" x-model="password">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            @click="showPassword = !showPassword" tabindex="-1">
                        <svg x-show="!showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.83M9.363 5.365A9.466 9.466 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411M6.423 6.423C4.5 7.86 3.1 9.8 2.458 12c.639 2.185 2.017 4.107 3.918 5.542"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-1.5 flex items-center gap-1.5" x-show="password.length > 0" x-transition.opacity>
                    <div class="flex h-1 flex-1 gap-1">
                        <template x-for="i in 4">
                            <div class="flex-1 rounded-full transition-colors duration-300"
                                 :class="i <= skorSandi ? warnaSandi : 'bg-slate-200'"></div>
                        </template>
                    </div>
                    <span class="text-xs font-medium text-slate-500" x-text="labelSandi"></span>
                </div>
            </div>

            <div>
                <label class="label" for="password_confirmation">Ulangi sandi <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="ikon-field h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input class="input input-ikon pr-10" id="password_confirmation" name="password_confirmation" required
                           :type="showConfirm ? 'text' : 'password'" x-model="passwordConfirm"
                           :class="sandiBeda && 'field-error'">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            @click="showConfirm = !showConfirm" tabindex="-1">
                        <svg x-show="!showConfirm" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirm" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.83M9.363 5.365A9.466 9.466 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411M6.423 6.423C4.5 7.86 3.1 9.8 2.458 12c.639 2.185 2.017 4.107 3.918 5.542"/>
                        </svg>
                    </button>
                </div>
                <p class="mt-1.5 text-xs font-medium" x-show="passwordConfirm.length > 0" x-transition.opacity
                   :class="sandiCocok ? 'text-emerald-600' : 'text-red-600'"
                   x-text="sandiCocok ? 'Sandi cocok.' : 'Sandi belum sama.'"></p>
            </div>
        </div>

        <button class="btn btn-cyan w-full" :disabled="submitting">
            <span x-show="!submitting" class="inline-flex items-center gap-2">Daftar</span>
            <span x-show="submitting" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Memproses&hellip;
            </span>
        </button>
    </form>

    <p class="mt-5 text-sm text-slate-500">
        Sudah punya akun? <a class="font-semibold text-navy-700 hover:underline" href="{{ route('login') }}">Masuk</a>
    </p>
</div>
@endsection
