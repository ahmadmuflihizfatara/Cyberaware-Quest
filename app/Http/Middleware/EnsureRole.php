<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return redirect()->route('login');
        }

        if (! $pengguna->punyaPeran(...$peran)) {
            abort(403, 'Akun Anda tidak memiliki peran '.implode(' atau ', $peran).' untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
