<?php

namespace App\Services\Ranap;

use Illuminate\Support\Facades\DB;

class RanapPenunjangService
{
    /**
     * Ambil hasil pemeriksaan laboratorium terverifikasi untuk pasien ranap
     */
    public function getHasilLab(string $noRawat): array
    {
        $raw = DB::table('detail_periksa_lab')
            ->join('template_laboratorium', function ($join) {
                $join->on('detail_periksa_lab.kd_jenis_prw', '=', 'template_laboratorium.kd_jenis_prw')
                     ->on('detail_periksa_lab.id_template', '=', 'template_laboratorium.id_template');
            })
            ->join('jns_perawatan_lab', 'detail_periksa_lab.kd_jenis_prw', '=', 'jns_perawatan_lab.kd_jenis_prw')
            ->where('detail_periksa_lab.no_rawat', $noRawat)
            ->select([
                'detail_periksa_lab.tgl_periksa',
                'detail_periksa_lab.jam',
                'detail_periksa_lab.kd_jenis_prw',
                'jns_perawatan_lab.nm_perawatan',
                'template_laboratorium.Pemeriksaan as item_pemeriksaan',
                'detail_periksa_lab.nilai',
                'template_laboratorium.satuan',
                'detail_periksa_lab.nilai_rujukan',
                'detail_periksa_lab.keterangan',
                'template_laboratorium.urut',
            ])
            ->orderBy('detail_periksa_lab.tgl_periksa', 'desc')
            ->orderBy('detail_periksa_lab.jam', 'desc')
            ->orderBy('template_laboratorium.urut', 'asc')
            ->get();

        // Kelompokkan per tanggal + jam + jenis perawatan
        $grouped = [];
        foreach ($raw as $item) {
            $key = $item->tgl_periksa . ' ' . $item->jam . ' - ' . $item->nm_perawatan;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'tgl_periksa'   => $item->tgl_periksa,
                    'jam'           => $item->jam,
                    'nm_perawatan'  => $item->nm_perawatan,
                    'items'         => [],
                ];
            }
            $grouped[$key]['items'][] = [
                'pemeriksaan'   => $item->item_pemeriksaan,
                'nilai'         => $item->nilai,
                'satuan'        => $item->satuan,
                'nilai_rujukan' => $item->nilai_rujukan,
                'keterangan'    => $item->keterangan,
            ];
        }

        return $grouped;
    }

    /**
     * Ambil foto dan hasil ekspertise radiologi
     */
    public function getHasilRadiologi(string $noRawat): array
    {
        $gambar = DB::table('gambar_radiologi')
            ->where('no_rawat', $noRawat)
            ->orderBy('tgl_periksa', 'desc')
            ->orderBy('jam', 'desc')
            ->get();

        $expertise = DB::table('hasil_radiologi')
            ->where('no_rawat', $noRawat)
            ->orderBy('tgl_periksa', 'desc')
            ->orderBy('jam', 'desc')
            ->get();

        return [
            'gambar'    => $gambar,
            'expertise' => $expertise,
        ];
    }

    /**
     * Ambil riwayat obat yang telah diberikan/disuntikkan di bangsal
     */
    public function getRiwayatPemberianObat(string $noRawat)
    {
        return DB::table('detail_pemberian_obat')
            ->join('databarang', 'detail_pemberian_obat.kode_brng', '=', 'databarang.kode_brng')
            ->where('detail_pemberian_obat.no_rawat', $noRawat)
            ->select([
                'detail_pemberian_obat.tgl_perawatan',
                'detail_pemberian_obat.jam',
                'detail_pemberian_obat.kode_brng',
                'databarang.nama_brng',
                'detail_pemberian_obat.jml',
                'databarang.kode_sat',
                'detail_pemberian_obat.biaya_obat',
                'detail_pemberian_obat.total',
            ])
            ->orderBy('detail_pemberian_obat.tgl_perawatan', 'desc')
            ->orderBy('detail_pemberian_obat.jam', 'desc')
            ->get();
    }
}
