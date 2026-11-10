<?php

namespace App\Services\Resep;

use App\Exceptions\ResepTerkunci;
use App\Services\Obat\StokObatService;
use App\Support\NomorUrut;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resep dokter bersama untuk ralan, ranap, dan IGD.
 *
 * Resep disimpan seperti e-resep Khanza: tanggal validasi (tgl_perawatan/jam) dan
 * penyerahan kosong ('0000-00-00'/'00:00:00') sampai apotek memprosesnya. Saat validasi,
 * apotek mengisi tgl_perawatan/jam dan menulis pemberian obat dengan waktu yang sama.
 */
class ResepService
{
    private const KOSONG_TGL = '0000-00-00';
    private const KOSONG_JAM = '00:00:00';

    public function __construct(private StokObatService $stok) {}

    /** @param array<int, array{kode_obat: string, jumlah: int|float, aturan_pakai: string}> $items */
    public function tambahObat(string $noRawat, array $items, string $kdDokter): array
    {
        $this->pastikanAdaStok(array_column($items, 'kode_obat'));

        return NomorUrut::denganRetry(function () use ($noRawat, $items, $kdDokter) {
            $noResep = $this->resepTerbuka($noRawat, $kdDokter);

            foreach ($items as $item) {
                $this->simpanAturan($item['aturan_pakai']);
                DB::table('resep_dokter')->updateOrInsert(
                    ['no_resep' => $noResep, 'kode_brng' => $item['kode_obat']],
                    ['jml' => $item['jumlah'], 'aturan_pakai' => $item['aturan_pakai']]
                );
            }

            return ['no_resep' => $noResep];
        });
    }

    /**
     * @param array{nama_racik: string, kd_racik: string, jml_dr: int, aturan_pakai: string, keterangan?: string,
     *              detail: array<int, array{kode_brng: string, p1: float, p2: float, kandungan: string, jml: float}>} $racikan
     */
    public function tambahRacikan(string $noRawat, array $racikan, string $kdDokter): array
    {
        $this->pastikanAdaStok(array_column($racikan['detail'], 'kode_brng'));

        return NomorUrut::denganRetry(function () use ($noRawat, $racikan, $kdDokter) {
            $noResep = $this->resepTerbuka($noRawat, $kdDokter);
            $this->simpanAturan($racikan['aturan_pakai']);

            $noRacik = (int) DB::table('resep_dokter_racikan')->where('no_resep', $noResep)->max('no_racik') + 1;

            DB::table('resep_dokter_racikan')->insert([
                'no_resep'     => $noResep,
                'no_racik'     => $noRacik,
                'nama_racik'   => $racikan['nama_racik'],
                'kd_racik'     => $racikan['kd_racik'],
                'jml_dr'       => $racikan['jml_dr'],
                'aturan_pakai' => $racikan['aturan_pakai'],
                'keterangan'   => $racikan['keterangan'] ?? '-',
            ]);

            foreach ($racikan['detail'] as $d) {
                DB::table('resep_dokter_racikan_detail')->insert([
                    'no_resep'  => $noResep,
                    'no_racik'  => $noRacik,
                    'kode_brng' => $d['kode_brng'],
                    'p1'        => $d['p1'],
                    'p2'        => $d['p2'],
                    'kandungan' => $d['kandungan'],
                    'jml'       => $d['jml'],
                ]);
            }

            return ['no_resep' => $noResep, 'no_racik' => $noRacik];
        });
    }

    public function hapusObat(string $noResep, string $kodeBrng): void
    {
        DB::transaction(function () use ($noResep, $kodeBrng) {
            $this->pastikanBelumDivalidasi($noResep);
            DB::table('resep_dokter')->where('no_resep', $noResep)->where('kode_brng', $kodeBrng)->delete();
            $this->hapusBilaKosong($noResep);
        });
    }

    public function hapusRacikan(string $noResep, int $noRacik): void
    {
        DB::transaction(function () use ($noResep, $noRacik) {
            $this->pastikanBelumDivalidasi($noResep);
            DB::table('resep_dokter_racikan_detail')->where('no_resep', $noResep)->where('no_racik', $noRacik)->delete();
            DB::table('resep_dokter_racikan')->where('no_resep', $noResep)->where('no_racik', $noRacik)->delete();
            $this->hapusBilaKosong($noResep);
        });
    }

    public function sudahDivalidasi(object $resepObat): bool
    {
        return !self::tanggalKosong($resepObat->tgl_perawatan ?? null);
    }

    public function status(object $resepObat): string
    {
        if (!$this->sudahDivalidasi($resepObat)) {
            return 'menunggu apotek';
        }

        return self::tanggalKosong($resepObat->tgl_penyerahan ?? null) ? 'diterima apotek' : 'diserahkan';
    }

    /** Semua resep kunjungan, terbaru dulu, dengan item, racikan, dan status. */
    public function resepKunjungan(string $noRawat): Collection
    {
        $resep = DB::table('resep_obat as r')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'r.kd_dokter')
            ->where('r.no_rawat', $noRawat)
            ->orderByDesc('r.tgl_peresepan')->orderByDesc('r.jam_peresepan')
            ->get(['r.*', 'd.nm_dokter']);
        if ($resep->isEmpty()) {
            return $resep;
        }

        $nomor = $resep->pluck('no_resep');
        $items = DB::table('resep_dokter as rd')
            ->join('databarang as b', 'b.kode_brng', '=', 'rd.kode_brng')
            ->whereIn('rd.no_resep', $nomor)
            ->orderBy('b.nama_brng')
            ->get(['rd.no_resep', 'rd.kode_brng', 'b.nama_brng', 'rd.jml', 'b.kode_sat', 'rd.aturan_pakai'])
            ->groupBy('no_resep');
        $detail = DB::table('resep_dokter_racikan_detail as rd')
            ->join('databarang as b', 'b.kode_brng', '=', 'rd.kode_brng')
            ->whereIn('rd.no_resep', $nomor)
            ->get(['rd.no_resep', 'rd.no_racik', 'rd.kode_brng', 'b.nama_brng', 'rd.kandungan', 'rd.jml'])
            ->groupBy(fn ($x) => $x->no_resep . '#' . $x->no_racik);
        $racikan = DB::table('resep_dokter_racikan as rr')
            ->leftJoin('metode_racik as m', 'm.kd_racik', '=', 'rr.kd_racik')
            ->whereIn('rr.no_resep', $nomor)
            ->orderBy('rr.no_racik')
            ->get(['rr.*', 'm.nm_racik'])
            ->map(function ($r) use ($detail) {
                $r->detail = $detail->get($r->no_resep . '#' . $r->no_racik, collect())->values();

                return $r;
            })
            ->groupBy('no_resep');

        return $resep->map(function ($r) use ($items, $racikan) {
            $r->items = $items->get($r->no_resep, collect())->values();
            $r->racikan = $racikan->get($r->no_resep, collect())->values();
            $r->status_teks = $this->status($r);
            $r->divalidasi = $this->sudahDivalidasi($r);

            return $r;
        });
    }

    /**
     * Resep kunjungan yang belum divalidasi, atau resep baru bila tidak ada.
     * Khanza memasang UNIQUE (tgl_perawatan, jam, no_rawat), sehingga satu kunjungan hanya bisa
     * punya satu resep belum divalidasi; dokter lain menambah ke resep yang sama.
     * Kunjungan dikunci supaya dua simpan bersamaan tidak berebut membuat resep.
     */
    private function resepTerbuka(string $noRawat, string $kdDokter): string
    {
        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->lockForUpdate()->first(['status_lanjut']);
        $status = $reg && strtolower((string) $reg->status_lanjut) === 'ranap' ? 'ranap' : 'ralan';

        $ada = DB::table('resep_obat')
            ->where('no_rawat', $noRawat)
            ->where(fn ($q) => $q->where('tgl_perawatan', self::KOSONG_TGL)->orWhereNull('tgl_perawatan'))
            ->orderByDesc('tgl_peresepan')->orderByDesc('jam_peresepan')
            ->value('no_resep');
        if ($ada) {
            return $ada;
        }

        $sekarang = now();
        $prefix = $sekarang->format('Ymd');
        $noResep = $prefix . sprintf('%04d', NomorUrut::berikutnya('resep_obat', 'no_resep', $prefix, 4));

        DB::table('resep_obat')->insert([
            'no_resep'       => $noResep,
            'tgl_perawatan'  => self::KOSONG_TGL,
            'jam'            => self::KOSONG_JAM,
            'no_rawat'       => $noRawat,
            'kd_dokter'      => $kdDokter,
            'tgl_peresepan'  => $sekarang->toDateString(),
            'jam_peresepan'  => $sekarang->format('H:i:s'),
            'status'         => $status,
            'tgl_penyerahan' => self::KOSONG_TGL,
            'jam_penyerahan' => self::KOSONG_JAM,
        ]);

        return $noResep;
    }

    private function pastikanAdaStok(array $kodeBarang): void
    {
        $habis = collect(array_unique($kodeBarang))->filter(fn ($k) => $this->stok->stok((string) $k) <= 0);
        if ($habis->isEmpty()) {
            return;
        }

        $nama = DB::table('databarang')->whereIn('kode_brng', $habis)->pluck('nama_brng')->map(fn ($n) => trim($n));
        throw new ResepTerkunci('Stok apotek habis: ' . $nama->implode(', ') . '. Pilih barang lain.');
    }

    private function pastikanBelumDivalidasi(string $noResep): void
    {
        $resep = DB::table('resep_obat')->where('no_resep', $noResep)->lockForUpdate()->first();
        if ($resep && $this->sudahDivalidasi($resep)) {
            throw new ResepTerkunci('Resep sudah divalidasi apotek dan tidak bisa diubah. Tambahkan item baru sebagai resep baru.');
        }
    }

    private function hapusBilaKosong(string $noResep): void
    {
        $sisa = DB::table('resep_dokter')->where('no_resep', $noResep)->exists()
            || DB::table('resep_dokter_racikan')->where('no_resep', $noResep)->exists();
        if (!$sisa) {
            DB::table('resep_obat')->where('no_resep', $noResep)->delete();
        }
    }

    /** Aturan pakai baru masuk ke master supaya bisa dipilih lagi. */
    private function simpanAturan(?string $aturan): void
    {
        $aturan = trim((string) $aturan);
        if ($aturan === '' || $aturan === '-' || mb_strlen($aturan) > 40) {
            return;
        }
        if (!DB::table('master_aturan_pakai')->where('aturan', $aturan)->exists()) {
            DB::table('master_aturan_pakai')->insert(['aturan' => $aturan]);
        }
    }

    private static function tanggalKosong(?string $tgl): bool
    {
        return $tgl === null || $tgl === '' || $tgl === self::KOSONG_TGL;
    }
}
