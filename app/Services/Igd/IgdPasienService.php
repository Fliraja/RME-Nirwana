<?php

namespace App\Services\Igd;

use App\Services\Kunjungan\RawatInap;
use App\Support\Alergi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IgdPasienService
{
    public function __construct(private RawatInap $rawatInap) {}

    /**
     * Sebelum jam ini pasien kemarin ikut tampil. Bukan jam pergantian jaga (07.00): dokter jaga malam
     * masih melengkapi asesmen sampai sekitar 08.00 (prod Jan-Okt 2026, 35 dari 50 asesmen terlambat).
     */
    private const BATAS_JAGA_MALAM = 9;

    /** @return array{0: Carbon, 1: Carbon} */
    public function rentangBawaan(Carbon $sekarang): array
    {
        $sampai = $sekarang->copy()->startOfDay();
        $dari = $sekarang->hour < self::BATAS_JAGA_MALAM ? $sampai->copy()->subDay() : $sampai->copy();

        return [$dari, $sampai];
    }

    public function daftar(string $dari, string $sampai, ?string $cari = null): Collection
    {
        $cari = trim((string) $cari);

        return DB::table('reg_periksa as r')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'r.no_rkm_medis')
            ->leftJoin('penjab as j', 'j.kd_pj', '=', 'r.kd_pj')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'r.kd_dokter')
            ->where('r.kd_poli', config('rme.igd.kd_poli'))
            ->where('r.stts', '<>', 'Batal')
            ->whereBetween('r.tgl_registrasi', [$dari, $sampai])
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('p.nm_pasien', 'like', "%{$cari}%")
                ->orWhere('r.no_rkm_medis', 'like', "%{$cari}%")
                ->orWhere('r.no_rawat', 'like', "%{$cari}%")))
            ->orderByDesc('r.tgl_registrasi')
            ->orderByDesc('r.jam_reg')
            ->select([
                'r.no_rawat', 'r.no_reg', 'r.tgl_registrasi', 'r.jam_reg', 'r.no_rkm_medis', 'r.umurdaftar',
                'r.sttsumur', 'r.status_lanjut', 'p.nm_pasien', 'p.jk', 'j.png_jawab', 'd.nm_dokter',
            ])
            ->selectRaw('EXISTS(SELECT 1 FROM kamar_inap k WHERE k.no_rawat = r.no_rawat) AS ada_kamar')
            ->selectRaw('EXISTS(SELECT 1 FROM penilaian_medis_igd m WHERE m.no_rawat = r.no_rawat) AS ada_asesmen')
            ->get()
            ->map(function ($b) {
                $b->rawat_inap = $b->status_lanjut === 'Ranap' || (bool) $b->ada_kamar;
                $b->ada_asesmen = (bool) $b->ada_asesmen;

                return $b;
            });
    }

    /** Data kepala halaman pasien; null bila kunjungan tidak ada atau bukan kunjungan IGD. */
    public function kepala(string $noRawat): ?array
    {
        $reg = DB::table('reg_periksa as r')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'r.no_rkm_medis')
            ->leftJoin('penjab as j', 'j.kd_pj', '=', 'r.kd_pj')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'r.kd_dokter')
            ->where('r.no_rawat', $noRawat)
            ->where('r.kd_poli', config('rme.igd.kd_poli'))
            ->first([
                'r.no_rawat', 'r.no_rkm_medis', 'r.tgl_registrasi', 'r.jam_reg', 'r.umurdaftar', 'r.sttsumur',
                'r.status_lanjut', 'r.stts', 'p.nm_pasien', 'p.jk', 'p.tgl_lahir', 'j.png_jawab', 'd.nm_dokter',
            ]);
        if (!$reg) {
            return null;
        }

        $asesmen = DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->first(['alergi']);
        $datang = Carbon::parse($reg->tgl_registrasi . ' ' . $reg->jam_reg);
        $ranap = $this->rawatInap->masukKamar($noRawat);
        $akhir = $ranap ?? Carbon::now();

        return [
            'reg'              => $reg,
            'datang'           => $datang,
            'alergi'           => $asesmen && Alergi::ada($asesmen->alergi) ? trim($asesmen->alergi) : null,
            'ada_asesmen'      => (bool) $asesmen,
            'lama_menit'       => max(0, (int) $datang->diffInMinutes($akhir, false)),
            'rawat_inap'       => $ranap !== null || $reg->status_lanjut === 'Ranap',
            'rawat_inap_sejak' => $ranap,
        ];
    }

}
