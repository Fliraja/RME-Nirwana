<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class NomorUrut
{
    /**
     * Nomor urut berikutnya untuk nomor berformat <prefix><urut>.
     * Hanya nomor dengan panjang tepat strlen(prefix) + $digit yang dihitung,
     * supaya nomor rusak/format lain tidak mengacaukan urutan.
     */
    public static function berikutnya(string $table, string $column, string $prefix, int $digit): int
    {
        $max = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->whereRaw("CHAR_LENGTH($column) = ?", [strlen($prefix) + $digit])
            ->max(DB::raw("CONVERT(RIGHT($column, $digit), signed)"));

        return ((int) $max) + 1;
    }

    /**
     * Jalankan $callback dalam transaksi; ulangi bila insert bentrok nomor
     * (duplicate key 1062) karena penyimpanan bersamaan dari user/Khanza lain.
     */
    public static function denganRetry(callable $callback, int $percobaan = 3): mixed
    {
        for ($ke = 1; ; $ke++) {
            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                if ($ke >= $percobaan || ($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
            }
        }
    }
}
