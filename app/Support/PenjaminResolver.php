<?php

namespace App\Support;

class PenjaminResolver
{
    public const UMUM = 'umum';
    public const BPJS = 'bpjs';
    public const PIHAK_KETIGA = 'pihak_ketiga';

    public static function klasifikasi(?string $kdPj): string
    {
        return match ($kdPj) {
            'UM'  => self::UMUM,
            'BPJ' => self::BPJS,
            default => self::PIHAK_KETIGA,
        };
    }

    private static function isRanap(?string $statusLanjut): bool
    {
        return strtolower($statusLanjut ?? '') === 'ranap';
    }

    /** Prefix katalog lab: RI/RJ + U/B/A */
    public static function labPrefix(?string $statusLanjut, ?string $kdPj): string
    {
        $prefix = self::isRanap($statusLanjut) ? 'RI' : 'RJ';
        $suffix = match (self::klasifikasi($kdPj)) {
            self::UMUM => 'U',
            self::BPJS => 'B',
            default    => 'A',
        };

        return $prefix . $suffix;
    }

    /** Prefix katalog radiologi: RAD.RJ / RAD.RI / RAD.B / RAD.P */
    public static function radiologiPrefix(?string $statusLanjut, ?string $kdPj): string
    {
        return match (self::klasifikasi($kdPj)) {
            self::BPJS         => 'RAD.B',
            self::PIHAK_KETIGA => 'RAD.P',
            default            => self::isRanap($statusLanjut) ? 'RAD.RI' : 'RAD.RJ',
        };
    }
}
