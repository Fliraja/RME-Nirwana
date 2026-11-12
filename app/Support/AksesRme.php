<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Siapa yang boleh memakai RME: dokter aktif yang spesialis, atau dokter IGD (kode berawalan DU.).
 * Tabel user Khanza berisi semua petugas, jadi akun saja tidak cukup. Spesialis dikenali dari
 * kd_sps karena kode dokternya tidak seragam (sppd1, SP.PD003/2024, D0000130, stht2).
 * Perawat menyusul setelah dibicarakan.
 */
final class AksesRme
{
    /** Penanda di sesi; sesi tanpa penanda ini (dibuat sebelum aturan ini) diminta login ulang. */
    public const SESI = 'akses_rme';

    /** Bukan spesialis: tanpa spesialisasi, dokter umum, dan IGD (dokter IGD lewat awalan DU.). */
    private const BUKAN_SPESIALIS = ['-', '', 'UMUM', 'S0003'];

    public static function boleh(?string $kdDokter): bool
    {
        if (!$kdDokter) {
            return false;
        }

        $dokter = DB::table('dokter')->where('kd_dokter', $kdDokter)->where('status', '1')->first(['kd_dokter', 'kd_sps']);
        if (!$dokter) {
            return false;
        }

        return str_starts_with($dokter->kd_dokter, (string) config('rme.igd.prefix_dokter'))
            || !in_array(trim((string) $dokter->kd_sps), self::BUKAN_SPESIALIS, true);
    }
}
