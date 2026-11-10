<?php

namespace App\Services\Igd;

use App\Exceptions\OrderIgdTerkunci;
use App\Support\ResumeFormat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IgdOrderService
{
    public function __construct(private IgdPasienService $pasien) {}

    /** Permintaan baru hanya untuk pasien IGD yang belum rawat inap. */
    public function pastikanBolehOrder(string $noRawat): void
    {
        $this->pastikanPasienIgd($noRawat);

        $ranap = $this->pasien->masukRanap($noRawat);
        if ($ranap) {
            throw new OrderIgdTerkunci($this->pesanTerkunci($ranap->format('H.i')), 409);
        }
    }

    public function pastikanPasienIgd(string $noRawat): void
    {
        $igd = DB::table('reg_periksa')->where('no_rawat', $noRawat)->where('kd_poli', config('rme.igd.kd_poli'))->exists();
        if (!$igd) {
            throw new OrderIgdTerkunci('Kunjungan IGD tidak ditemukan.', 404);
        }
    }

    public function pesanTerkunci(string $jam): string
    {
        return "Pasien sudah rawat inap sejak {$jam}. Permintaan baru lewat modul Ranap.";
    }

    /** Pastikan nomor order milik kunjungan ini, supaya order kunjungan lain tidak bisa dihapus lewat URL. */
    public function pastikanMilik(string $tabel, string $noorder, string $noRawat): void
    {
        if (!DB::table($tabel)->where('noorder', $noorder)->where('no_rawat', $noRawat)->exists()) {
            throw new OrderIgdTerkunci('Permintaan tidak ditemukan pada kunjungan ini.', 404);
        }
    }

    public function daftarLab(string $noRawat): Collection
    {
        $order = $this->daftar('permintaan_lab', $noRawat);
        $item = DB::table('permintaan_pemeriksaan_lab as p')
            ->join('jns_perawatan_lab as j', 'j.kd_jenis_prw', '=', 'p.kd_jenis_prw')
            ->whereIn('p.noorder', $order->pluck('noorder'))
            ->get(['p.noorder', 'p.kd_jenis_prw', 'j.nm_perawatan', 'p.stts_bayar'])
            ->groupBy('noorder');

        return $order->map(function ($o) use ($item, $noRawat) {
            $baris = $item->get($o->noorder, collect());
            $o->pemeriksaan = $baris->pluck('nm_perawatan')->map(fn ($n) => trim($n))->implode(', ');
            $o->status = $this->statusLab($o, $baris->pluck('kd_jenis_prw')->all(), $noRawat);
            $o->boleh_hapus = !$this->diproses($o) && !$baris->contains('stts_bayar', 'Sudah');

            return $o;
        });
    }

    public function daftarRadiologi(string $noRawat): Collection
    {
        $order = $this->daftar('permintaan_radiologi', $noRawat);
        $item = DB::table('permintaan_pemeriksaan_radiologi as p')
            ->join('jns_perawatan_radiologi as j', 'j.kd_jenis_prw', '=', 'p.kd_jenis_prw')
            ->whereIn('p.noorder', $order->pluck('noorder'))
            ->get(['p.noorder', 'j.nm_perawatan', 'p.stts_bayar'])
            ->groupBy('noorder');

        return $order->map(function ($o) use ($item) {
            $baris = $item->get($o->noorder, collect());
            $o->pemeriksaan = $baris->pluck('nm_perawatan')->map(fn ($n) => trim($n))->implode(', ');
            $o->status = $this->statusDasar($o);
            $o->boleh_hapus = !$this->diproses($o) && !$baris->contains('stts_bayar', 'Sudah');

            return $o;
        });
    }

    public function usulanDiagnosaKlinis(string $noRawat): ?string
    {
        $dx = trim((string) DB::table('penilaian_medis_igd')->where('no_rawat', $noRawat)->value('diagnosis'));

        // permintaan_*.diagnosa_klinis hanya varchar(80); diagnosis asesmen bisa sampai 500.
        return $dx === '' || $dx === '-' ? null : mb_substr(preg_replace('/\s+/', ' ', $dx), 0, 80);
    }

    private function daftar(string $tabel, string $noRawat): Collection
    {
        return DB::table("$tabel as o")
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'o.dokter_perujuk')
            ->where('o.no_rawat', $noRawat)
            ->orderByDesc('o.tgl_permintaan')->orderByDesc('o.jam_permintaan')
            ->get(['o.*', 'd.nm_dokter']);
    }

    private static function kosong(?string $tgl): bool
    {
        return $tgl === null || $tgl === '' || $tgl === '0000-00-00';
    }

    /** Diproses penunjang = sampel sudah diambil (hasil Task 1: ambil sampel tidak mengubah stts_bayar). */
    private function diproses(object $o): bool
    {
        return !self::kosong($o->tgl_sampel);
    }

    private function statusDasar(object $o): string
    {
        if (!self::kosong($o->tgl_hasil)) {
            return 'hasil keluar';
        }

        return self::kosong($o->tgl_sampel) ? 'menunggu sampel' : 'sampel diambil';
    }

    private function statusLab(object $o, array $kodeJenis, string $noRawat): string
    {
        $status = $this->statusDasar($o);
        if ($status !== 'hasil keluar' || !$kodeJenis) {
            return $status;
        }

        $diLuar = DB::table('detail_periksa_lab')
            ->where('no_rawat', $noRawat)->whereIn('kd_jenis_prw', $kodeJenis)
            ->where('tgl_periksa', $o->tgl_hasil)->where('jam', $o->jam_hasil)
            ->get(['nilai', 'nilai_rujukan'])
            ->filter(fn ($r) => ResumeFormat::flagLab((string) $r->nilai, (string) $r->nilai_rujukan) !== '')
            ->count();

        return $diLuar > 0 ? "hasil keluar, {$diLuar} di luar rujukan" : $status;
    }
}
