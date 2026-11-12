<?php

namespace App\Services\Ranap;

/**
 * Satu baris pemeriksaan_ranap bisa berisi SOAP dan TTV sekaligus (umum dari Khanza desktop).
 * Tab SOAP dan TTV menghapus bagiannya masing-masing; baris baru dibuang bila bagian lain kosong.
 */
final class BarisPemeriksaanRanap
{
    /** Nilai kosong sama dengan yang ditulis saat menyimpan TTV saja. */
    public const SOAP_KOSONG = [
        'keluhan' => '', 'pemeriksaan' => '', 'penilaian' => '', 'rtl' => '', 'instruksi' => '', 'evaluasi' => '-',
    ];

    /** Nilai kosong sama dengan yang ditulis saat menyimpan SOAP saja. Alergi diisi dari form TTV. */
    public const TTV_KOSONG = [
        'suhu_tubuh' => '', 'tensi' => '-', 'nadi' => '', 'respirasi' => '', 'tinggi' => '', 'berat' => '',
        'spo2' => '', 'gcs' => '', 'kesadaran' => 'Compos Mentis', 'alergi' => '',
    ];

    public static function adaSoap(object $baris): bool
    {
        return self::adaIsi($baris, array_keys(self::SOAP_KOSONG));
    }

    public static function adaTtv(object $baris): bool
    {
        $kolom = array_diff(array_keys(self::TTV_KOSONG), ['kesadaran']);

        return self::adaIsi($baris, $kolom);
    }

    private static function adaIsi(object $baris, array $kolom): bool
    {
        foreach ($kolom as $k) {
            $nilai = trim((string) ($baris->$k ?? ''));
            if ($nilai !== '' && $nilai !== '-') {
                return true;
            }
        }

        return false;
    }
}
