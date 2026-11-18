<?php

namespace App\Support;

/**
 * Batas isian formulir KFR. Angka dari kolom Khanza (layanan_kedokteran_fisik_rehabilitasi,
 * uji_fungsi_kfr) dan rme_kfr; dipakai form (data-batas) dan validasi server.
 */
final class BatasIsianKfr
{
    public const BATAS = [
        'diagnosa_fungsi'      => 200,
        'diagnosis_fungsional' => 50,
        'penunjang'            => 500,
        'target'               => 300,
        'edukasi'              => 300,
        'ket_suspek'           => 70,
        'kesimpulan'           => 100,
        'rekomendasi'          => 100,
    ];

    public const LABEL = [
        'diagnosa_fungsi'      => 'Diagnosis fungsi (formulir asesmen)',
        'diagnosis_fungsional' => 'Diagnosis fungsi (lembar uji fungsi)',
        'penunjang'            => 'Pemeriksaan penunjang',
        'target'               => 'Target / goal',
        'edukasi'              => 'Edukasi',
        'suspek'               => 'Suspek penyakit akibat kerja',
        'ket_suspek'           => 'Keterangan suspek penyakit akibat kerja',
        'kesimpulan'           => 'Kesimpulan',
        'rekomendasi'          => 'Rekomendasi',
    ];

    /** Aturan validasi dengan awalan nama field, mis. "kfr.". */
    public static function aturan(string $awalan = ''): array
    {
        $aturan = [];
        foreach (BatasIsianPemeriksaan::aturan(self::BATAS) as $kolom => $isi) {
            $aturan[$awalan . $kolom] = $isi;
        }
        $aturan[$awalan . 'suspek'] = ['nullable', 'in:Tidak,Ya'];
        $aturan[$awalan . 'ket_suspek'][] = 'required_if:' . $awalan . 'suspek,Ya';

        return $aturan;
    }

    /** Aplikasi tidak memuat terjemahan validasi bawaan, jadi aturan selain batas panjang diberi kalimat sendiri. */
    public static function pesan(string $awalan = ''): array
    {
        return [
            $awalan . 'suspek.in'              => 'Pilih Tidak atau Ya untuk suspek penyakit akibat kerja.',
            $awalan . 'ket_suspek.required_if' => 'Tulis keterangan suspek penyakit akibat kerja bila memilih Ya.',
        ];
    }

    public static function label(string $awalan = ''): array
    {
        $label = [];
        foreach (self::LABEL as $kolom => $teks) {
            $label[$awalan . $kolom] = $teks;
        }

        return $label;
    }
}
