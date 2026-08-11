<?php

namespace App\Http\Controllers;

use App\Models\LogIntegrasi;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function formLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($kredensial)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        if (Auth::user()->status_akun !== 'aktif') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun ini dinonaktifkan. Hubungi panitia.',
            ]);
        }

        $request->session()->regenerate();
        LogIntegrasi::catat('auth', 'login', Auth::user()->email);

        return redirect()->intended(self::berandaPeran(Auth::user()->peranUtama()));
    }

    public function formRegistrasi()
    {
        return view('auth.registrasi');
    }

    public function registrasi(Request $request)
    {
        $data = $request->validate([
            'nama_pengguna' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('pengguna', 'email')],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $pengguna = DB::transaction(function () use ($data) {
            $pengguna = Pengguna::create([
                'nama_pengguna' => $data['nama_pengguna'],
                'email' => $data['email'],
                'kata_sandi_hash' => Hash::make($data['password']),
            ]);

            $pengguna->peran()->attach(Peran::where('kode_peran', 'peserta')->value('id_peran'));

            // Profil peserta ditautkan lewat email (lihat catatan pada model Pengguna).
            Peserta::firstOrCreate(
                ['email' => $data['email']],
                ['nama_peserta' => $data['nama_pengguna'], 'no_hp' => $data['no_hp'] ?? null],
            );

            return $pengguna;
        });

        Auth::login($pengguna);
        $request->session()->regenerate();
        LogIntegrasi::catat('auth', 'registrasi', $pengguna->email);

        return redirect()->route('peserta.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }

    public static function berandaPeran(string $peran): string
    {
        return match ($peran) {
            'admin', 'penyelenggara' => route('admin.dashboard'),
            'fasilitator' => route('fasilitator.dashboard'),
            default => route('peserta.dashboard'),
        };
    }
}
