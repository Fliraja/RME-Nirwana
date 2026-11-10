<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DokterIgd
{
    public static function adalah(?string $kdDokter): bool
    {
        $prefix = (string) config('rme.igd.prefix_dokter');

        if (!$kdDokter || $prefix === '' || !str_starts_with($kdDokter, $prefix)) {
            return false;
        }

        return DB::table('dokter')->where('kd_dokter', $kdDokter)->where('status', '1')->exists();
    }
}
