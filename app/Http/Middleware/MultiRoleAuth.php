<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\AksesRme;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MultiRoleAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && session(AksesRme::SESI) !== true) {
            // Sesi dari sebelum aturan akses RME, atau akun yang tidak lagi berhak.
            Auth::logout();
            $request->session()->invalidate();
        } elseif (Auth::check() || session('login_admin')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Sesi berakhir, silakan login ulang.'], 401);
        }

        return redirect()->route('login')->with('error', 'Silahkan login terlebih dahulu');
    }
}
