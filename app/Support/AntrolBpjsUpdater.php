<?php

namespace App\Support;

use App\Models\AntrolBpjs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AntrolBpjsUpdater
{
    /**
     * Catat jam periksa perawat (first-write-wins).
     */
    public static function tandaiPerawat(string $noRawat, ?string $kdPj, ?Carbon $waktu = null): void
    {
        if (PenjaminResolver::klasifikasi($kdPj) !== PenjaminResolver::BPJS) {
            return;
        }

        try {
            $now = $waktu ?? Carbon::now();
            AntrolBpjs::where('no_rawat', $noRawat)
                ->where(function ($q) {
                    $q->whereNull('jam_periksa_perawat')
                      ->orWhere('jam_periksa_perawat', '00:00:00');
                })
                ->update([
                    'jam_periksa_perawat' => $now->format('H:i:s')
                ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat jam periksa perawat ke antrol_bpjs: ' . $e->getMessage(), [
                'no_rawat' => $noRawat,
                'kd_pj' => $kdPj,
            ]);
        }
    }

    /**
     * Hitung jam dokter dengan rule batas minimal 61 detik di atas jam periksa perawat.
     */
    public static function hitungJamDokter(?string $jamPerawat, Carbon $now): string
    {
        if ($jamPerawat && $jamPerawat !== '00:00:00') {
            $waktuPerawat = Carbon::createFromTimeString($jamPerawat);
            $batasMinimal = $waktuPerawat->copy()->addSeconds(61);

            if ($now->lessThan($batasMinimal)) {
                return $batasMinimal->format('H:i:s');
            }
        }

        return $now->format('H:i:s');
    }

    /**
     * Catat jam periksa dokter (selalu overwrite jam submit + batas minimal 61 detik).
     */
    public static function tandaiDokter(string $noRawat, ?string $kdPj, ?Carbon $waktu = null): void
    {
        if (PenjaminResolver::klasifikasi($kdPj) !== PenjaminResolver::BPJS) {
            return;
        }

        try {
            $now = $waktu ?? Carbon::now();

            $jamPerawat = AntrolBpjs::where('no_rawat', $noRawat)
                ->whereNotNull('jam_periksa_perawat')
                ->where('jam_periksa_perawat', '!=', '00:00:00')
                ->value('jam_periksa_perawat');

            $jamDokter = self::hitungJamDokter($jamPerawat, $now);

            AntrolBpjs::where('no_rawat', $noRawat)
                ->update([
                    'jam_periksa_dokter' => $jamDokter
                ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat jam periksa dokter ke antrol_bpjs: ' . $e->getMessage(), [
                'no_rawat' => $noRawat,
                'kd_pj' => $kdPj,
            ]);
        }
    }
}
