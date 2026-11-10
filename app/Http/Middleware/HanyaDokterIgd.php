<?php

namespace App\Http\Middleware;

use App\Support\DokterIgd;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HanyaDokterIgd
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(DokterIgd::adalah(session('decrypted_id')), 403, 'Halaman IGD hanya untuk dokter IGD.');

        return $next($request);
    }
}
