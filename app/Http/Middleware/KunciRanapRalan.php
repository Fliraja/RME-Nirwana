<?php

namespace App\Http\Middleware;

use App\Services\Kunjungan\RawatInap;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak permintaan lab/radiologi/resep lewat rute /ralan untuk pasien yang sudah rawat inap.
 * Rute /ranap memakai controller dan service yang sama, jadi kuncinya di rute, bukan di service.
 */
class KunciRanapRalan
{
    public function __construct(private RawatInap $rawatInap) {}

    public function handle(Request $request, Closure $next): Response
    {
        $noRawat = $this->noRawat($request);
        $pesan = $noRawat ? $this->rawatInap->pesanKunci($noRawat) : null;
        if ($pesan) {
            return response()->json(['status' => 'error', 'message' => $pesan], 409);
        }

        return $next($request);
    }

    /** Simpan membawa no_rawat; hapus hanya membawa nomor resep atau nomor order. */
    private function noRawat(Request $request): ?string
    {
        if ($request->filled('no_rawat')) {
            return (string) $request->input('no_rawat');
        }
        if ($noResep = $request->route('no_resep')) {
            return DB::table('resep_obat')->where('no_resep', $noResep)->value('no_rawat');
        }
        if ($noorder = $request->route('noorder')) {
            $tabel = $request->routeIs('ralan.delete-lab') ? 'permintaan_lab' : 'permintaan_radiologi';

            return DB::table($tabel)->where('noorder', $noorder)->value('no_rawat');
        }

        return null;
    }
}
