<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Alergi
{
    /** Isian "tidak ada" beserta salah ketiknya ("tdk ada", "tidakl ada", "Tidak ada alergi") dianggap tanpa alergi. */
    public static function ada(?string $teks): bool
    {
        $huruf = preg_replace('/[^a-z]/', '', strtolower($teks ?? ''));
        if ($huruf === '') {
            return false;
        }

        return !preg_match('/^t{1,2}(idak|dk|ak)[a-z]?(ad[ae]?)?(alergi)?$|^(none|nihil|disangkal|negatif)$/', $huruf);
    }

    /** Alergi terakhir yang tercatat di TTV ranap; catatan "tidak ada" yang lebih baru tidak menghapusnya. */
    public static function terakhirRanap(string $noRawat): ?string
    {
        return DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->where('alergi', '<>', '')
            ->orderByDesc('tgl_perawatan')
            ->orderByDesc('jam_rawat')
            ->pluck('alergi')
            ->first(fn ($a) => self::ada($a));
    }
}
