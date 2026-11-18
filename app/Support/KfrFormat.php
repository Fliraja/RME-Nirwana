<?php

namespace App\Support;

/**
 * Aturan teks formulir Rehab Medik (KFR). Instruksi SOAP poli P003 hampir selalu
 * berbentuk "2X/Minggu, 12X Terapi" (sering dengan tab/baris kosong dari Khanza desktop).
 */
final class KfrFormat
{
    /** @return array{frekuensi: string, durasi: string} */
    public static function pecahInstruksi(?string $instruksi): array
    {
        $baris = preg_split('/\R/u', (string) $instruksi);
        $teks = trim(implode(' ', array_filter(array_map('trim', $baris), 'strlen')));

        if ($teks === '' || $teks === '-') {
            return ['frekuensi' => '', 'durasi' => ''];
        }

        $bagian = explode(',', $teks, 2);

        return ['frekuensi' => trim($bagian[0]), 'durasi' => trim($bagian[1] ?? '')];
    }

    /**
     * Koneksi Khanza non-strict memotong teks kepanjangan tanpa galat, jadi kolom pendek
     * diisi potongan di batas kata. PDF tetap mencetak teks sumber yang utuh.
     */
    public static function potong(string $teks, int $batas): string
    {
        if (mb_strlen($teks) <= $batas) {
            return $teks;
        }

        $potongan = mb_substr($teks, 0, $batas);
        if (mb_substr($teks, $batas, 1) === ' ') {
            return rtrim($potongan, " +,");
        }

        $spasi = mb_strrpos($potongan, ' ');
        if ($spasi === false) {
            return $potongan;
        }

        return rtrim(mb_substr($potongan, 0, $spasi), " +,");
    }
}
