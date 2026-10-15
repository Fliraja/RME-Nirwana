<?php

namespace App\Support;

final class ResumeFormat
{
    private const TTV = ['TD' => 'tensi', 'N' => 'nadi', 'S' => 'suhu_tubuh', 'RR' => 'respirasi',
        'SpO2' => 'spo2', 'GCS' => 'gcs', 'TB' => 'tinggi', 'BB' => 'berat'];

    public static function ttv(object $baris): string
    {
        $bagian = [];
        foreach (self::TTV as $label => $kolom) {
            $nilai = trim((string) ($baris->{$kolom} ?? ''));
            if ($nilai !== '' && $nilai !== '-') {
                $bagian[] = "{$label} {$nilai}";
            }
        }
        // Kesadaran punya default 'Compos Mentis' di baris SOAP; tanpa TTV lain berarti belum dinilai.
        $kesadaran = trim((string) ($baris->kesadaran ?? ''));
        if ($kesadaran !== '' && $bagian !== []) {
            $bagian[] = "Kes. {$kesadaran}";
        }

        return implode(', ', $bagian);
    }

    public static function flagLab(string $nilai, string $rujukan): string
    {
        $v = self::angka($nilai);
        if ($v === null) {
            return '';
        }

        if (preg_match('/^\s*([\d.,]+)\s*-\s*([\d.,]+)\s*$/', $rujukan, $m)) {
            $a = self::angka($m[1]);
            $b = self::angka($m[2]);
            if (!$a || !$b || !self::sejenis($v, $a, $b)) {
                return '';
            }
            return $v[0] < $a[0] ? 'L' : ($v[0] > $b[0] ? 'H' : '');
        }
        if (preg_match('/^\s*<=?\s*([\d.,]+)\s*$/', $rujukan, $m)) {
            $b = self::angka($m[1]);
            return ($b && self::sejenis($v, $b) && $v[0] > $b[0]) ? 'H' : '';
        }
        if (preg_match('/^\s*>=?\s*([\d.,]+)\s*$/', $rujukan, $m)) {
            $a = self::angka($m[1]);
            return ($a && self::sejenis($v, $a) && $v[0] < $a[0]) ? 'L' : '';
        }

        return '';
    }

    public static function barisLab(string $nama, string $nilai, string $satuan, string $rujukan): string
    {
        $teks = trim($nama) . ': ' . trim($nilai) . (trim($satuan) !== '' ? ' ' . trim($satuan) : '');
        if (trim($rujukan) !== '' && trim($rujukan) !== '-') {
            $teks .= ' (ruj. ' . trim($rujukan) . ')';
        }
        $flag = self::flagLab($nilai, $rujukan);

        return $flag !== '' ? "{$teks} [{$flag}]" : $teks;
    }

    public static function barisObat(string $nama, string $jumlah, string $aturan): string
    {
        $teks = trim($nama) . ' ' . trim($jumlah);

        return trim($aturan) !== '' ? $teks . ' (' . trim($aturan) . ')' : $teks;
    }

    /** @return array{0:float,1:bool}|null [nilai, pakai pemisah ribuan titik] */
    private static function angka(string $s): ?array
    {
        $s = trim($s);
        if (!preg_match('/^\d+(?:[.,]\d+)*$/', $s)) {
            return null;
        }
        $ribuan = (bool) preg_match('/^\d{1,3}(?:\.\d{3})+$/', $s);
        if ($ribuan) {
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);
        if (substr_count($s, '.') > 1) {
            return null;
        }

        return [(float) $s, $ribuan];
    }

    /** Format penulisan berbeda (18.000 vs 4.5) tidak dibandingkan: lebih baik tanpa tanda daripada salah. */
    private static function sejenis(array ...$angka): bool
    {
        return count(array_unique(array_map(fn ($a) => $a[1], $angka))) === 1;
    }
}
