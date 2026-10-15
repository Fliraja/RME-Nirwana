<?php

namespace App\Services\Ralan;

use App\Support\KodeSearch;
use Illuminate\Support\Facades\DB;

class DiagnosaProsedurService
{
    /** @return array<int, array{id:string, text:string}> */
    public function searchIcd10(string $search): array
    {
        $normalized = KodeSearch::normalize($search);

        $rows = DB::table('penyakit')
            ->where(function ($q) use ($search, $normalized) {
                $q->where('kd_penyakit', 'like', "%$search%")
                  ->orWhere('nm_penyakit', 'like', "%$search%")
                  ->orWhereRaw("REPLACE(kd_penyakit, '.', '') LIKE ?", ["%$normalized%"]);
            })
            ->limit(20)
            ->get();

        return $rows->map(fn ($i) => [
            'id'   => $i->kd_penyakit,
            'text' => $i->kd_penyakit . ' - ' . $i->nm_penyakit,
        ])->all();
    }

    /** @return array<int, array{id:string, text:string}> */
    public function searchIcd9(string $search): array
    {
        $normalized = KodeSearch::normalize($search);

        $rows = DB::table('icd9')
            ->where(function ($q) use ($search, $normalized) {
                $q->where('kode', 'like', "%$search%")
                  ->orWhere('deskripsi_panjang', 'like', "%$search%")
                  ->orWhere('deskripsi_pendek', 'like', "%$search%")
                  ->orWhereRaw("REPLACE(kode, '.', '') LIKE ?", ["%$normalized%"]);
            })
            ->limit(20)
            ->get();

        return $rows->map(fn ($i) => [
            'id'   => $i->kode,
            'text' => $i->kode . ' - ' . ($i->deskripsi_panjang ?: $i->deskripsi_pendek),
        ])->all();
    }
}
