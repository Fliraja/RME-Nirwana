<?php

namespace App\Services\Igd;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IgdPasienService
{
    /** Jam sebelum ini dianggap masih jaga malam, sehingga pasien kemarin ikut tampil. */
    private const BATAS_JAGA_MALAM = 7;

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

    /** Waktu pasien mulai rawat inap, atau null bila masih di IGD/rawat jalan. */
    public function masukRanap(string $noRawat): ?Carbon
    {
        $kamar = DB::table('kamar_inap')->where('no_rawat', $noRawat)
            ->orderBy('tgl_masuk')->orderBy('jam_masuk')->first(['tgl_masuk', 'jam_masuk']);
        if ($kamar) {
            return Carbon::parse($kamar->tgl_masuk . ' ' . $kamar->jam_masuk);
        }

        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first(['status_lanjut', 'tgl_registrasi', 'jam_reg']);

        return $reg && $reg->status_lanjut === 'Ranap'
            ? Carbon::parse($reg->tgl_registrasi . ' ' . $reg->jam_reg)
            : null;
    }
}
