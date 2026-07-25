<?php

namespace App\Support;

class KodeSearch
{
    /**
     * Buang titik & spasi supaya "99.52" cocok saat diketik "9952".
     */
    public static function normalize(string $term): string
    {
        return str_replace(['.', ' '], '', $term);
    }
}
