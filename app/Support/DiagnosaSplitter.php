<?php

namespace App\Support;

final class DiagnosaSplitter
{
    public const MAKS_KARAKTER = 80;
    public const MAKS_SEKUNDER = 4;

    /** @return array{utama:string, sekunder:list<string>} */
    public static function pecah(?string $teks): array
    {
        $teks = trim((string) $teks);
        if ($teks === '' || $teks === '-') {
            return ['utama' => '', 'sekunder' => []];
        }

        $teks = preg_replace('/\r\n?|\n/', ';', $teks);
        $bagian = preg_split('/\s*(?:[;,+]|\s\/\s|\b(?:dengan|dgn)\b)\s*/iu', $teks);

        $bagian = array_values(array_filter(array_map(function (string $b): string {
            $b = preg_replace('/^\d{1,2}[.)]\s+/u', '', trim($b));
            return trim(preg_replace('/\s+/u', ' ', $b), " \t.-:");
        }, $bagian), fn (string $b) => $b !== ''));

        if ($bagian === []) {
            return ['utama' => '', 'sekunder' => []];
        }

        $utama = array_shift($bagian);
        $sekunder = array_slice($bagian, 0, self::MAKS_SEKUNDER);
        if (count($bagian) > self::MAKS_SEKUNDER) {
            // Diagnosis ke-6 dst. tidak punya kolom; digabung agar tidak hilang.
            $sekunder[self::MAKS_SEKUNDER - 1] = implode(', ', array_slice($bagian, self::MAKS_SEKUNDER - 1));
        }

        return [
            'utama'    => self::potong($utama),
            'sekunder' => array_map(fn (string $s) => self::potong($s), $sekunder),
        ];
    }

    private static function potong(string $s): string
    {
        return mb_substr($s, 0, self::MAKS_KARAKTER);
    }
}
