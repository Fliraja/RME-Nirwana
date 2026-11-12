<?php

namespace App\Support;

/**
 * Panjang kolom pemeriksaan_ralan / pemeriksaan_ranap Khanza. Koneksi aplikasi
 * non-strict, jadi isian yang melewati batas akan dipotong MySQL tanpa galat;
 * aturan ini menolaknya lebih dulu. Angka yang sama dipakai form (data-batas, maxlength).
 */
final class BatasIsianPemeriksaan
{
    public const SOAP_RANAP = [
        'keluhan' => 400, 'pemeriksaan' => 400, 'penilaian' => 400,
        'rtl' => 500, 'instruksi' => 400, 'evaluasi' => 400,
    ];

    /** Nama isian form ralan: objek = pemeriksaan, plan = rtl. */
    public const SOAP_RALAN = [
        'keluhan' => 2000, 'objek' => 2000, 'penilaian' => 2000, 'plan' => 2000, 'instruksi' => 2000,
    ];

    public const TTV = [
        'suhu_tubuh' => 5, 'tensi' => 7, 'nadi' => 3, 'respirasi' => 3,
        'tinggi' => 5, 'berat' => 5, 'spo2' => 3, 'gcs' => 10,
    ];

    public const ALERGI_RANAP = 400;
    public const ALERGI_RALAN = 2000;

    public const LABEL = [
        'keluhan'     => 'Subjek (Keluhan)',
        'pemeriksaan' => 'Objek (Pemeriksaan Fisik)',
        'objek'       => 'Objek (Pemeriksaan Fisik)',
        'penilaian'   => 'Assesmen (Diagnosa)',
        'rtl'         => 'Plan (Terapi/Tindakan)',
        'plan'        => 'Plan (Terapi/Tindakan)',
        'instruksi'   => 'Instruksi / RTL',
        'evaluasi'    => 'Evaluasi',
        'suhu_tubuh'  => 'Suhu',
        'tensi'       => 'Tensi',
        'nadi'        => 'Nadi',
        'respirasi'   => 'Respirasi',
        'tinggi'      => 'Tinggi',
        'berat'       => 'Berat',
        'spo2'        => 'SpO2',
        'gcs'         => 'GCS',
        'alergi'      => 'Alergi',
    ];

    /** @param array<string,int> $batas */
    public static function aturan(array $batas): array
    {
        $aturan = [];
        foreach ($batas as $kolom => $maks) {
            $aturan[$kolom] = ['nullable', 'string', self::paling($maks)];
        }

        return $aturan;
    }

    public static function label(): array
    {
        return self::LABEL;
    }

    /** Pesan menyebut jumlah sekarang supaya dokter tahu berapa yang perlu diringkas. */
    private static function paling(int $maks): \Closure
    {
        return function (string $atribut, mixed $nilai, \Closure $gagal) use ($maks) {
            $jumlah = mb_strlen((string) $nilai);
            if ($jumlah > $maks) {
                $gagal(":attribute paling banyak {$maks} karakter, sekarang {$jumlah}.");
            }
        };
    }
}
