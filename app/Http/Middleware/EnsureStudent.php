<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === 'student') {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Akses hanya tersedia untuk mahasiswa.'], 403);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'IS-Path hanya dapat diakses menggunakan akun mahasiswa.',
        ]);
    }
}
