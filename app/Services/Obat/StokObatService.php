<?php

namespace App\Services\Obat;

use Illuminate\Support\Facades\DB;

class StokObatService
{
    private function bangsal(): string
    {
        return (string) config('rme.obat.kd_bangsal_stok');
    }

    /**
     * Hasil pencarian barang untuk pilihan obat/BHP, lengkap dengan stok gudang apotek.
     * Barang habis tetap dikembalikan (tidak bisa dipilih) dan diurutkan paling akhir.
     */
    public function cari(string $kata, int $batas = 20): array
    {
        $kata = trim($kata);

        return DB::table('databarang as b')
            ->leftJoin('gudangbarang as g', function ($j) {
                $j->on('g.kode_brng', '=', 'b.kode_brng')->where('g.kd_bangsal', '=', $this->bangsal());
            })
            ->where('b.status', '1')
            ->where(fn ($q) => $q->where('b.nama_brng', 'like', "%{$kata}%")->orWhere('b.kode_brng', 'like', "%{$kata}%"))
            ->orderByRaw('COALESCE(g.stok, 0) <= 0')
            ->orderBy('b.nama_brng')
            ->limit($batas)
            ->get(['b.kode_brng', 'b.nama_brng', 'b.kode_sat', 'b.stokminimal', DB::raw('COALESCE(g.stok, 0) AS stok')])
            ->map(function ($b) {
                $stok = (float) $b->stok;
                $tingkat = $this->tingkat($stok, (float) $b->stokminimal);

                return [
                    'id'       => $b->kode_brng,
                    'text'     => $b->nama_brng,
                    'stok'     => $stok,
                    'satuan'   => (string) $b->kode_sat,
                    'tingkat'  => $tingkat,
                    'label'    => $this->label($tingkat, $stok, (string) $b->kode_sat),
                    'disabled' => $tingkat === 'habis',
                    'urut'     => $tingkat === 'habis' ? 1 : 0,
                ];
            })
            ->all();
    }

    public function stok(string $kodeBrng): float
    {
        return (float) DB::table('gudangbarang')
            ->where('kode_brng', $kodeBrng)->where('kd_bangsal', $this->bangsal())->value('stok');
    }

    public function tingkat(float $stok, float $stokMinimal): string
    {
        if ($stok <= 0) {
            return 'habis';
        }

        return $stokMinimal > 0 && $stok <= $stokMinimal ? 'tipis' : 'aman';
    }

    private function label(string $tingkat, float $stok, string $satuan): string
    {
        $angka = trim(rtrim(rtrim(number_format($stok, 2, ',', '.'), '0'), ','));

        return match ($tingkat) {
            'habis' => 'habis',
            'tipis' => trim("sisa {$angka} {$satuan}") . ', hampir habis',
            default => trim("stok {$angka} {$satuan}"),
        };
    }
}
