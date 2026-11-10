<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Hasil lab satu kunjungan yang nilainya di luar rujukan; dipakai referensi resume ranap dan asesmen IGD. */
final class LabDiLuarRujukan
{
    /**
     * Per paket pemeriksaan (paket + waktu periksa), berurutan waktu.
     *
     * @return Collection<int, array{paket: string, tanggal: string, baris: list<string>}>
     */
    public static function perPaket(string $noRawat): Collection
    {
        return DB::table('detail_periksa_lab as d')
            ->join('template_laboratorium as t', function ($j) {
                $j->on('t.id_template', '=', 'd.id_template')->on('t.kd_jenis_prw', '=', 'd.kd_jenis_prw');
            })
            ->join('jns_perawatan_lab as j', 'j.kd_jenis_prw', '=', 'd.kd_jenis_prw')
            ->where('d.no_rawat', $noRawat)
            ->orderBy('d.tgl_periksa')->orderBy('d.jam')->orderBy('t.urut')
            ->get(['d.kd_jenis_prw', 'd.tgl_periksa', 'd.jam', 'j.nm_perawatan', 't.Pemeriksaan', 'd.nilai', 't.satuan', 'd.nilai_rujukan'])
            ->filter(fn ($r) => ResumeFormat::flagLab((string) $r->nilai, (string) $r->nilai_rujukan) !== '')
            ->groupBy(fn ($r) => "{$r->kd_jenis_prw}|{$r->tgl_periksa}|{$r->jam}")
            ->map(fn ($baris) => [
                'paket'   => trim($baris->first()->nm_perawatan),
                'tanggal' => $baris->first()->tgl_periksa,
                'baris'   => $baris->map(fn ($r) => ResumeFormat::barisLab(
                    (string) $r->Pemeriksaan, (string) $r->nilai, (string) $r->satuan, (string) $r->nilai_rujukan
                ))->values()->all(),
            ])
            ->values();
    }

    /** @return list<string> satu baris per pemeriksaan di luar rujukan */
    public static function baris(string $noRawat): array
    {
        return self::perPaket($noRawat)->flatMap(fn ($p) => $p['baris'])->values()->all();
    }
}
