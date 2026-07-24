<?php

namespace App\Support;

class PrioritasResolver
{
    /**
     * Tentukan prioritas: primer = item pertama ber-validcode '1';
     * jika tidak ada, item pertama. Sisanya 2,3,4,... urut input.
     *
     * @param array<int, array{kode:string, validcode:string}> $items terurut (existing dulu, lalu baru)
     * @return array<string, int> map kode => prioritas
     */
    public static function hitung(array $items): array
    {
        // Dedupe, pertahankan kemunculan pertama.
        $seen = [];
        $ordered = [];
        foreach ($items as $it) {
            if (isset($seen[$it['kode']])) {
                continue;
            }
            $seen[$it['kode']] = true;
            $ordered[] = $it;
        }

        if ($ordered === []) {
            return [];
        }

        $primerIndex = null;
        foreach ($ordered as $i => $it) {
            if ((string) $it['validcode'] === '1') {
                $primerIndex = $i;
                break;
            }
        }
        if ($primerIndex === null) {
            $primerIndex = 0;
        }

        $result = [$ordered[$primerIndex]['kode'] => 1];
        $next = 2;
        foreach ($ordered as $i => $it) {
            if ($i === $primerIndex) {
                continue;
            }
            $result[$it['kode']] = $next++;
        }

        return $result;
    }
}
