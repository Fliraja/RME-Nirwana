<?php

namespace App\Services\Ranap;

use App\Models\KamarInap;
use App\Models\RegPeriksa;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class RanapDashboardService
{
    /** Baris kamar_inap lama saat pasien pindah; bukan kejadian masuk/pulang. */
    private const PINDAH_KAMAR = 'Pindah Kamar';

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
                'kamar_inap.diagnosa_awal',
                'penjab.png_jawab',
            ])
            ->selectSub($this->masukAwal('tgl_masuk'), 'tgl_masuk')
            ->selectSub($this->masukAwal('jam_masuk'), 'jam_masuk')
            ->selectSub($this->namaDpjp(), 'dpjp');

        $this->filterDpjp($query, $kdDokter);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pasien.nm_pasien', 'like', "%{$search}%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%{$search}%")
                  ->orWhere('reg_periksa.no_rawat', 'like', "%{$search}%")
                  ->orWhere('bangsal.nm_bangsal', 'like', "%{$search}%")
                  ->orWhere('kamar.kd_kamar', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('tgl_masuk', 'asc')
                     ->orderBy('jam_masuk', 'asc')
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
            ->where('kamar_inap.tgl_keluar', $tanggal)
            ->whereNotIn('kamar_inap.stts_pulang', ['-', self::PINDAH_KAMAR])
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.umur',
                'kamar.kd_kamar',
                'kamar.kelas',
                'bangsal.nm_bangsal',
                'kamar_inap.tgl_keluar',
                'kamar_inap.stts_pulang',
                'penjab.png_jawab',
            ])
            ->selectSub($this->masukAwal('tgl_masuk'), 'tgl_masuk')
            ->selectSub(
                DB::table('kamar_inap as k0')
                    ->selectRaw('SUM(k0.lama)')
                    ->whereColumn('k0.no_rawat', 'kamar_inap.no_rawat'),
                'lama'
            )
            ->selectSub($this->namaDpjp(), 'dpjp');

        $this->filterDpjp($query, $kdDokter);

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
            ->where('kamar_inap.stts_pulang', '-');

        // Masuk = kamar pertama rawat ini dimulai hari ini (pindah kamar tidak dihitung).
        $queryMasukHariIni = DB::table('kamar_inap')
            ->where('kamar_inap.tgl_masuk', $today)
            ->whereNotExists(function ($q) {
                $q->from('kamar_inap as k0')
                  ->whereColumn('k0.no_rawat', 'kamar_inap.no_rawat')
                  ->where(function ($w) {
                      $w->whereColumn('k0.tgl_masuk', '<', 'kamar_inap.tgl_masuk')
                        ->orWhere(function ($s) {
                            $s->whereColumn('k0.tgl_masuk', 'kamar_inap.tgl_masuk')
                              ->whereColumn('k0.jam_masuk', '<', 'kamar_inap.jam_masuk');
                        });
                  });
            });

        $queryPulangHariIni = DB::table('kamar_inap')
            ->where('kamar_inap.tgl_keluar', $today)
            ->whereNotIn('kamar_inap.stts_pulang', ['-', self::PINDAH_KAMAR]);

        foreach ([$queryAktif, $queryMasukHariIni, $queryPulangHariIni] as $q) {
            $this->filterDpjp($q, $kdDokter);
        }

        return [
            'totalPasienAktif' => $queryAktif->distinct()->count('kamar_inap.no_rawat'),
            'masukHariIni'     => $queryMasukHariIni->distinct()->count('kamar_inap.no_rawat'),
            'pulangHariIni'    => $queryPulangHariIni->distinct()->count('kamar_inap.no_rawat'),
        ];
    }

    /** Tanggal/jam masuk kamar pertama, supaya lama dirawat tidak terpotong pindah kamar. */
    private function masukAwal(string $kolom): Builder
    {
        return DB::table('kamar_inap as k0')
            ->select("k0.$kolom")
            ->whereColumn('k0.no_rawat', 'kamar_inap.no_rawat')
            ->orderBy('k0.tgl_masuk')
            ->orderBy('k0.jam_masuk')
            ->limit(1);
    }

    /** Semua DPJP digabung dalam satu kolom agar pasien multi-DPJP tidak tampil dobel. */
    private function namaDpjp(): Builder
    {
        return DB::table('dpjp_ranap as dp')
            ->join('dokter as dk', 'dp.kd_dokter', '=', 'dk.kd_dokter')
            ->selectRaw("GROUP_CONCAT(dk.nm_dokter ORDER BY dk.nm_dokter SEPARATOR ', ')")
            ->whereColumn('dp.no_rawat', 'kamar_inap.no_rawat');
    }

    private function filterDpjp(Builder $query, ?string $kdDokter): void
    {
        if (!$kdDokter) {
            return;
        }

        $query->whereExists(function ($q) use ($kdDokter) {
            $q->from('dpjp_ranap')
              ->whereColumn('dpjp_ranap.no_rawat', 'kamar_inap.no_rawat')
              ->where('dpjp_ranap.kd_dokter', $kdDokter);
        });
    }
}
