<?php

namespace App\Services\Ranap;

use App\Models\KamarInap;
use App\Models\RegPeriksa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RanapDashboardService
{
    /**
     * Ambil daftar pasien rawat inap yang masih aktif (belum pulang)
     */
    public function getPasienAktif(?string $kdDokter = null, ?string $search = null)
    {
        $query = DB::table('kamar_inap')
            ->join('reg_periksa', 'kamar_inap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('kamar', 'kamar_inap.kd_kamar', '=', 'kamar.kd_kamar')
            ->join('bangsal', 'kamar.kd_bangsal', '=', 'bangsal.kd_bangsal')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->leftJoin('dpjp_ranap', 'reg_periksa.no_rawat', '=', 'dpjp_ranap.no_rawat')
            ->leftJoin('dokter', 'dpjp_ranap.kd_dokter', '=', 'dokter.kd_dokter')
            ->where('kamar_inap.stts_pulang', '-')
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.umur',
                'pasien.tgl_lahir',
                'kamar.kd_kamar',
                'kamar.kelas',
                'bangsal.nm_bangsal',
                'kamar_inap.tgl_masuk',
                'kamar_inap.jam_masuk',
                'kamar_inap.diagnosa_awal',
                'penjab.png_jawab',
                'dokter.nm_dokter as dpjp',
                'dpjp_ranap.kd_dokter'
            ]);

        if ($kdDokter) {
            $query->where('dpjp_ranap.kd_dokter', $kdDokter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pasien.nm_pasien', 'like', "%{$search}%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%{$search}%")
                  ->orWhere('reg_periksa.no_rawat', 'like', "%{$search}%")
                  ->orWhere('bangsal.nm_bangsal', 'like', "%{$search}%")
                  ->orWhere('kamar.kd_kamar', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('kamar_inap.tgl_masuk', 'asc')
                     ->orderBy('kamar_inap.jam_masuk', 'asc')
                     ->get();
    }

    /**
     * Ambil riwayat pasien ranap yang pulang berdasarkan tanggal
     */
    public function getPasienPulang(string $tanggal, ?string $kdDokter = null, ?string $search = null)
    {
        $query = DB::table('kamar_inap')
            ->join('reg_periksa', 'kamar_inap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('kamar', 'kamar_inap.kd_kamar', '=', 'kamar.kd_kamar')
            ->join('bangsal', 'kamar.kd_bangsal', '=', 'bangsal.kd_bangsal')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->leftJoin('dpjp_ranap', 'reg_periksa.no_rawat', '=', 'dpjp_ranap.no_rawat')
            ->leftJoin('dokter', 'dpjp_ranap.kd_dokter', '=', 'dokter.kd_dokter')
            ->where('kamar_inap.tgl_keluar', $tanggal)
            ->where('kamar_inap.stts_pulang', '!=', '-')
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.umur',
                'kamar.kd_kamar',
                'kamar.kelas',
                'bangsal.nm_bangsal',
                'kamar_inap.tgl_masuk',
                'kamar_inap.tgl_keluar',
                'kamar_inap.lama',
                'kamar_inap.stts_pulang',
                'penjab.png_jawab',
                'dokter.nm_dokter as dpjp',
                'dpjp_ranap.kd_dokter'
            ]);

        if ($kdDokter) {
            $query->where('dpjp_ranap.kd_dokter', $kdDokter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pasien.nm_pasien', 'like', "%{$search}%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%{$search}%")
                  ->orWhere('reg_periksa.no_rawat', 'like', "%{$search}%")
                  ->orWhere('bangsal.nm_bangsal', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('kamar_inap.jam_keluar', 'desc')->get();
    }

    /**
     * Hitung statistik rawat inap untuk dashboard cards
     */
    public function getStatistik(?string $kdDokter = null): array
    {
        $today = Carbon::today()->toDateString();

        $queryAktif = DB::table('kamar_inap')
            ->where('stts_pulang', '-');
        if ($kdDokter) {
            $queryAktif->join('dpjp_ranap', 'kamar_inap.no_rawat', '=', 'dpjp_ranap.no_rawat')
                       ->where('dpjp_ranap.kd_dokter', $kdDokter);
        }

        $queryMasukHariIni = DB::table('kamar_inap')
            ->where('tgl_masuk', $today);
        if ($kdDokter) {
            $queryMasukHariIni->join('dpjp_ranap', 'kamar_inap.no_rawat', '=', 'dpjp_ranap.no_rawat')
                              ->where('dpjp_ranap.kd_dokter', $kdDokter);
        }

        $queryPulangHariIni = DB::table('kamar_inap')
            ->where('tgl_keluar', $today);
        if ($kdDokter) {
            $queryPulangHariIni->join('dpjp_ranap', 'kamar_inap.no_rawat', '=', 'dpjp_ranap.no_rawat')
                               ->where('dpjp_ranap.kd_dokter', $kdDokter);
        }

        return [
            'totalPasienAktif' => $queryAktif->count(),
            'masukHariIni'     => $queryMasukHariIni->count(),
            'pulangHariIni'    => $queryPulangHariIni->count(),
        ];
    }
}
