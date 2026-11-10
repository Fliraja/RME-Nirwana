<?php

namespace App\Services\Obat;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Obat/BHP yang benar-benar diberikan apotek, beserta aturan pakai dari apotek.
 *
 * Saat validasi resep, apotek menulis detail_pemberian_obat, aturan_pakai, obat_racikan, dan
 * detail_obat_racikan dengan tgl_perawatan + jam + no_rawat yang sama dengan resep_obat.
 * Aturan dicocokkan dengan kunci lengkap, bukan hanya kode barang, karena barang yang sama
 * bisa diberikan beberapa kali dengan aturan berbeda.
 */
class ObatDiberikan
{
    public function untukKunjungan(string $noRawat): Collection
    {
        return $this->ambil($this->dasar()->where('d.no_rawat', $noRawat));
    }

    /** Pemberian untuk satu resep; kosong bila resep belum divalidasi apotek. */
    public function untukResep(object $resepObat): Collection
    {
        if (empty($resepObat->tgl_perawatan) || $resepObat->tgl_perawatan === '0000-00-00') {
            return collect();
        }

        return $this->ambil($this->dasar()
            ->where('d.no_rawat', $resepObat->no_rawat)
            ->where('d.tgl_perawatan', $resepObat->tgl_perawatan)
            ->where('d.jam', $resepObat->jam));
    }

    private function dasar(): Builder
    {
        $kunci = fn (string $alias) => function ($j) use ($alias) {
            $j->on("$alias.tgl_perawatan", '=', 'd.tgl_perawatan')
                ->on("$alias.jam", '=', 'd.jam')
                ->on("$alias.no_rawat", '=', 'd.no_rawat');
        };

        return DB::table('detail_pemberian_obat as d')
            ->join('databarang as b', 'b.kode_brng', '=', 'd.kode_brng')
            ->leftJoin('golongan_barang as g', 'g.kode', '=', 'b.kode_golongan')
            ->leftJoin('aturan_pakai as a', function ($j) use ($kunci) {
                $kunci('a')($j);
                $j->on('a.kode_brng', '=', 'd.kode_brng');
            })
            ->leftJoin('detail_obat_racikan as dr', function ($j) use ($kunci) {
                $kunci('dr')($j);
                $j->on('dr.kode_brng', '=', 'd.kode_brng');
            })
            ->leftJoin('obat_racikan as r', function ($j) use ($kunci) {
                $kunci('r')($j);
                $j->on('r.no_racik', '=', 'dr.no_racik');
            })
            ->orderBy('d.tgl_perawatan')->orderBy('d.jam')->orderBy('b.nama_brng')
            ->select([
                'd.tgl_perawatan', 'd.jam', 'd.kode_brng', 'b.nama_brng', 'd.jml', 'b.kode_sat', 'd.status',
                'g.nama as golongan', 'a.aturan', 'dr.no_racik', 'r.nama_racik', 'r.aturan_pakai as aturan_racik',
            ]);
    }

    private function ambil(Builder $q): Collection
    {
        return $q->get()->map(fn ($r) => [
            'tgl_perawatan' => $r->tgl_perawatan,
            'jam'           => $r->jam,
            'kode_brng'     => $r->kode_brng,
            'nama_brng'     => trim((string) $r->nama_brng),
            'jml'           => $r->jml,
            'satuan'        => (string) $r->kode_sat,
            'status'        => $r->status,
            'golongan'      => $r->golongan,
            'racikan'       => $r->no_racik !== null,
            'no_racik'      => $r->no_racik,
            'nama_racik'    => $r->nama_racik,
            'aturan'        => self::isi($r->no_racik !== null ? $r->aturan_racik : $r->aturan),
        ]);
    }

    private static function isi(?string $teks): ?string
    {
        $teks = trim((string) $teks);

        return $teks === '' ? null : $teks;
    }
}
