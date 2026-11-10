<?php

namespace App\Services\Igd;

use App\Services\Obat\ObatDiberikan;
use App\Services\Resep\ResepService;
use App\Support\LabDiLuarRujukan;
use App\Support\ResumeFormat;
use Illuminate\Support\Facades\DB;

/**
 * Referensi isian Penunjang dan Tatalaksana asesmen IGD, dari data kunjungan ini.
 * Hanya membaca; dokter yang memilih apa yang dipakai.
 */
class AsesmenIgdReferensi
{
    public function __construct(
        private IgdOrderService $order,
        private ResepService $resep,
        private ObatDiberikan $obatDiberikan,
    ) {}

    /** @return array{lab: list<string>, lab_menunggu: list<string>, radiologi: list<string>, radiologi_menunggu: list<string>, tatalaksana: list<string>} */
    public function untuk(string $noRawat): array
    {
        return [
            'lab'                => LabDiLuarRujukan::baris($noRawat),
            'lab_menunggu'       => $this->menunggu($this->order->daftarLab($noRawat)),
            'radiologi'          => $this->ekspertise($noRawat),
            'radiologi_menunggu' => $this->menunggu($this->order->daftarRadiologi($noRawat)),
            'tatalaksana'        => $this->tatalaksana($noRawat),
        ];
    }

    /** Permintaan yang belum ada hasilnya, terlama dulu. */
    private function menunggu($daftar): array
    {
        return $daftar->reverse()
            ->filter(fn ($o) => !str_starts_with($o->status, 'hasil keluar') && $o->pemeriksaan !== '')
            ->map(fn ($o) => "{$o->pemeriksaan}: menunggu hasil")
            ->values()->all();
    }

    private function ekspertise(string $noRawat): array
    {
        return DB::table('hasil_radiologi')->where('no_rawat', $noRawat)
            ->orderBy('tgl_periksa')->orderBy('jam')->pluck('hasil')
            ->map(fn ($h) => trim((string) $h))
            ->filter(fn ($h) => $h !== '' && $h !== '-')
            ->values()->all();
    }

    /**
     * Satu baris per item resep, resep terlama dulu. Setelah apotek memvalidasi, yang dipakai adalah
     * barang dan aturan dari apotek (yang benar-benar diberikan), bukan tulisan dokter.
     */
    private function tatalaksana(string $noRawat): array
    {
        $baris = [];
        foreach ($this->resep->resepKunjungan($noRawat)->reverse() as $r) {
            $diberikan = $r->divalidasi ? $this->obatDiberikan->untukResep($r) : collect();

            if ($diberikan->isNotEmpty()) {
                foreach ($diberikan->groupBy(fn ($d) => $d['racikan'] ? 'r' . $d['no_racik'] : 'o' . $d['kode_brng'] . $d['jam']) as $grup) {
                    $d = $grup->first();
                    $baris[] = $d['racikan']
                        ? self::baris((string) $d['nama_racik'], '', (string) $d['aturan'])
                        : self::baris($d['nama_brng'], self::angka($d['jml']) . ' ' . $d['satuan'], (string) $d['aturan']);
                }
                continue;
            }

            foreach ($r->items as $it) {
                $baris[] = self::baris((string) $it->nama_brng, self::angka($it->jml) . ' ' . $it->kode_sat, (string) $it->aturan_pakai);
            }
            foreach ($r->racikan as $rk) {
                $baris[] = self::baris((string) $rk->nama_racik, self::angka($rk->jml_dr) . ' ' . ($rk->nm_racik ?? ''), (string) $rk->aturan_pakai);
            }
        }

        return $baris;
    }

    private static function baris(string $nama, string $jumlah, string $aturan): string
    {
        // Racikan dari apotek tidak membawa jumlah; barisObat lalu menyisakan dua spasi.
        return preg_replace('/ {2,}/', ' ', trim(ResumeFormat::barisObat($nama, trim($jumlah), $aturan)));
    }

    /** 2 → "2", 2.5 → "2,5" (sama dengan kartu resep). */
    private static function angka($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    }
}
