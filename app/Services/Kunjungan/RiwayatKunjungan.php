<?php

namespace App\Services\Kunjungan;

use App\Models\RegPeriksa;
use App\Services\Ranap\ResumeRanapReferensi;
use App\Support\ResumeFormat;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat kunjungan satu pasien untuk tab Riwayat (IGD, ralan, ranap) dan PDF riwayat.
 * Isi klinis diambil dari tempat yang benar per jenis kunjungan: SOAP poli, asesmen IGD,
 * atau kamar_inap + CPPT dokter untuk rawat inap. Satu set query per halaman, bukan per kunjungan.
 */
class RiwayatKunjungan
{
    public function halaman(string $noRkmMedis, int $perHalaman = 5, string $namaHalaman = 'riwayat_page'): LengthAwarePaginator
    {
        $halaman = $this->dasar($noRkmMedis)->paginate($perHalaman, ['*'], $namaHalaman);
        $halaman->setCollection($this->susun($halaman->getCollection()));

        return $halaman;
    }

    /** Untuk PDF: n kunjungan terakhir tanpa halaman. */
    public function terakhir(string $noRkmMedis, int $jumlah = 5): Collection
    {
        return $this->susun($this->dasar($noRkmMedis)->limit($jumlah)->get());
    }

    private function dasar(string $noRkmMedis)
    {
        return RegPeriksa::with(['poliklinik', 'dokter'])
            ->where('no_rkm_medis', $noRkmMedis)
            ->where('stts', '!=', 'Batal')
            ->orderByDesc('tgl_registrasi')
            ->orderByDesc('jam_reg');
    }

    private function susun(Collection $reg): Collection
    {
        $no = $reg->pluck('no_rawat')->all();
        if (!$no) {
            return collect();
        }

        $soap = DB::table('pemeriksaan_ralan')->whereIn('no_rawat', $no)
            ->orderBy('tgl_perawatan')->orderBy('jam_rawat')->get()->keyBy('no_rawat');
        $igd = DB::table('penilaian_medis_igd as m')->leftJoin('dokter as d', 'd.kd_dokter', '=', 'm.kd_dokter')
            ->whereIn('m.no_rawat', $no)->orderBy('m.tanggal')->get(['m.*', 'd.nm_dokter'])->keyBy('no_rawat');
        $kamar = DB::table('kamar_inap')->whereIn('no_rawat', $no)
            ->orderBy('tgl_masuk')->orderBy('jam_masuk')->get()->groupBy('no_rawat');
        $dpjp = DB::table('dpjp_ranap as p')->join('dokter as d', 'd.kd_dokter', '=', 'p.kd_dokter')
            ->whereIn('p.no_rawat', $no)->orderBy('d.nm_dokter')->get(['p.no_rawat', 'd.nm_dokter'])->groupBy('no_rawat');
        $cppt = $this->cpptDokter($no);
        $lab = $this->lab($no);
        $radiologi = $this->radiologi($no);
        $gambar = DB::table('gambar_radiologi')->whereIn('no_rawat', $no)
            ->orderBy('tgl_periksa')->orderBy('jam')->get()->groupBy('no_rawat');
        $obat = $this->obat($no);

        return $reg->map(function ($r) use ($soap, $igd, $kamar, $dpjp, $cppt, $lab, $radiologi, $gambar, $obat) {
            $n = $r->no_rawat;
            $jenis = $r->status_lanjut === 'Ranap' ? 'ranap'
                : ($r->kd_poli === config('rme.igd.kd_poli') ? 'igd' : 'poli');

            return (object) [
                'no_rawat'  => $n,
                'tanggal'   => $r->tgl_registrasi,
                'jenis'     => $jenis,
                'unit'      => match ($jenis) {
                    'ranap' => 'Rawat inap',
                    'igd'   => 'IGD',
                    default => trim($r->poliklinik->nm_poli ?? 'Poli'),
                },
                'dokter'    => $r->dokter->nm_dokter ?? null,
                'lewat_igd' => $r->kd_poli === config('rme.igd.kd_poli'),
                'soap'      => $this->soap($soap->get($n)),
                'igd'       => $this->asesmenIgd($igd->get($n)),
                'ranap'     => $jenis === 'ranap' ? $this->ranap($kamar->get($n, collect()), $dpjp->get($n, collect()), $cppt->get($n, collect())) : null,
                'lab'       => $lab->get($n, ['jumlah' => 0, 'luar' => [], 'per_tanggal' => []]),
                'radiologi' => $radiologi->get($n, collect())->all(),
                'gambar'    => $gambar->get($n, collect())
                    ->map(fn ($g) => rtrim((string) config('app.simrs_url'), '/') . '/radiologi/' . $g->lokasi_gambar)->all(),
                'obat'      => $obat->get($n, collect())->all(),
            ];
        });
    }

    private static function isi($nilai): string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '-' ? '' : $nilai;
    }

    private function soap(?object $p): ?array
    {
        if (!$p) {
            return null;
        }
        $isi = ['S' => self::isi($p->keluhan), 'O' => self::isi($p->pemeriksaan), 'A' => self::isi($p->penilaian), 'P' => self::isi($p->rtl)];

        return array_filter($isi) ? $isi : null;
    }

    private function asesmenIgd(?object $m): ?array
    {
        if (!$m) {
            return null;
        }

        $ttv = collect([
            'TD' => $m->td, 'N' => $m->nadi, 'RR' => $m->rr, 'S' => $m->suhu, 'SpO2' => $m->spo, 'GCS' => $m->gcs,
        ])->map(fn ($v) => self::isi($v))->filter()
            ->map(fn ($v, $k) => $k === 'SpO2' ? "SpO2 {$v}%" : "{$k} {$v}")->implode(' · ');

        return [
            'dokter'      => $m->nm_dokter,
            'waktu'       => $m->tanggal,
            'keluhan'     => self::isi($m->keluhan_utama),
            'pemeriksaan' => trim($ttv . ($ttv !== '' && self::isi($m->ket_fisik) !== '' ? '. ' : '') . self::isi($m->ket_fisik)),
            'diagnosis'   => self::isi($m->diagnosis),
            'tatalaksana' => self::isi($m->tata),
        ];
    }

    private function ranap(Collection $kamar, Collection $dpjp, Collection $cppt): array
    {
        $nyata = $kamar->filter(fn ($k) => $k->stts_pulang !== 'Pindah Kamar');
        $keluar = $kamar->last();
        $masihDirawat = !$keluar || $keluar->stts_pulang === '-';

        return [
            'masuk'         => $kamar->first()?->tgl_masuk,
            'keluar'        => $masihDirawat ? null : $keluar->tgl_keluar,
            'lama'          => $masihDirawat ? null : max(1, (int) $kamar->sum('lama')),
            'masih_dirawat' => $kamar->isNotEmpty() && $masihDirawat,
            'dx_awal'       => self::isi($kamar->first()?->diagnosa_awal),
            'dx_akhir'      => self::isi($kamar->reverse()->first(fn ($k) => self::isi($k->diagnosa_akhir) !== '')?->diagnosa_akhir),
            'pulang'        => $masihDirawat ? null : self::isi($nyata->last()?->stts_pulang),
            'dpjp'          => $dpjp->pluck('nm_dokter')->unique()->implode(', '),
            'cppt'          => $cppt->all(),
        ];
    }

    /** CPPT yang ditulis dokter dan berisi SOAP, terbaru dulu. Catatan perawat/bidan tidak diringkas. */
    private function cpptDokter(array $no): Collection
    {
        return DB::table('pemeriksaan_ranap as x')->join('dokter as d', 'd.kd_dokter', '=', 'x.nip')
            ->whereIn('x.no_rawat', $no)
            ->orderByDesc('x.tgl_perawatan')->orderByDesc('x.jam_rawat')
            ->get(['x.no_rawat', 'x.tgl_perawatan', 'x.jam_rawat', 'x.keluhan', 'x.pemeriksaan', 'x.penilaian', 'x.rtl', 'd.nm_dokter'])
            ->map(fn ($c) => [
                'no_rawat' => $c->no_rawat,
                'waktu'    => "{$c->tgl_perawatan} {$c->jam_rawat}",
                'dokter'   => $c->nm_dokter,
                'S' => self::isi($c->keluhan), 'O' => self::isi($c->pemeriksaan),
                'A' => self::isi($c->penilaian), 'P' => self::isi($c->rtl),
            ])
            ->filter(fn ($c) => $c['S'] . $c['O'] . $c['A'] . $c['P'] !== '')
            ->groupBy('no_rawat');
    }

    private function lab(array $no): Collection
    {
        return DB::table('detail_periksa_lab as d')
            ->join('template_laboratorium as t', function ($j) {
                $j->on('t.id_template', '=', 'd.id_template')->on('t.kd_jenis_prw', '=', 'd.kd_jenis_prw');
            })
            ->join('jns_perawatan_lab as j', 'j.kd_jenis_prw', '=', 'd.kd_jenis_prw')
            ->whereIn('d.no_rawat', $no)
            ->orderBy('d.tgl_periksa')->orderBy('d.jam')->orderBy('t.urut')
            ->get(['d.no_rawat', 'd.tgl_periksa', 'j.nm_perawatan', 't.Pemeriksaan', 'd.nilai', 't.satuan', 'd.nilai_rujukan'])
            ->groupBy('no_rawat')
            ->map(function (Collection $baris) {
                $teks = fn ($r) => ResumeFormat::barisLab((string) $r->Pemeriksaan, (string) $r->nilai, (string) $r->satuan, (string) $r->nilai_rujukan);
                $luar = $baris->filter(fn ($r) => ResumeFormat::flagLab((string) $r->nilai, (string) $r->nilai_rujukan) !== '');

                return [
                    'jumlah'      => $baris->count(),
                    'luar'        => $luar->map($teks)->values()->all(),
                    'per_tanggal' => $baris->groupBy('tgl_periksa')
                        ->map(fn ($hari) => $hari->groupBy(fn ($r) => trim($r->nm_perawatan))->map(fn ($p) => $p->map($teks)->all())->all())
                        ->all(),
                ];
            });
    }

    private function radiologi(array $no): Collection
    {
        $nama = DB::table('periksa_radiologi as p')
            ->join('jns_perawatan_radiologi as j', 'j.kd_jenis_prw', '=', 'p.kd_jenis_prw')
            ->whereIn('p.no_rawat', $no)
            ->get(['p.no_rawat', 'p.tgl_periksa', 'p.jam', 'j.nm_perawatan'])
            ->groupBy(fn ($p) => "{$p->no_rawat}|{$p->tgl_periksa}|{$p->jam}");

        return DB::table('hasil_radiologi')->whereIn('no_rawat', $no)
            ->orderBy('tgl_periksa')->orderBy('jam')->get()
            ->filter(fn ($h) => self::isi($h->hasil) !== '')
            ->map(fn ($h) => [
                'no_rawat'    => $h->no_rawat,
                'tanggal'     => $h->tgl_periksa,
                'pemeriksaan' => $nama->get("{$h->no_rawat}|{$h->tgl_periksa}|{$h->jam}", collect())
                    ->pluck('nm_perawatan')->map(fn ($v) => trim($v))->unique()->implode(', ') ?: 'Radiologi',
                'hasil'       => self::isi($h->hasil),
            ])
            ->groupBy('no_rawat');
    }

    /** Obat yang diberikan apotek, dijumlah per barang. BHP disaring dari namanya (pola resume ranap). */
    private function obat(array $no): Collection
    {
        return DB::table('detail_pemberian_obat as o')
            ->join('databarang as b', 'b.kode_brng', '=', 'o.kode_brng')
            ->whereIn('o.no_rawat', $no)
            ->groupBy('o.no_rawat', 'o.kode_brng', 'b.nama_brng', 'b.kode_sat')
            ->orderByRaw('MIN(o.tgl_perawatan), MIN(o.jam)')
            ->get(['o.no_rawat', 'b.nama_brng', 'b.kode_sat', DB::raw('SUM(o.jml) as jml')])
            ->reject(fn ($o) => preg_match(ResumeRanapReferensi::BUKAN_OBAT, $o->nama_brng))
            ->map(fn ($o) => [
                'no_rawat' => $o->no_rawat,
                'nama'     => trim($o->nama_brng),
                'jumlah'   => rtrim(rtrim(number_format((float) $o->jml, 2, ',', '.'), '0'), ','),
                'satuan'   => trim((string) $o->kode_sat),
            ])
            ->groupBy('no_rawat');
    }

    public static function tanggal(?string $tgl, string $format = 'd/m/Y'): string
    {
        return $tgl && !str_starts_with($tgl, '0000') ? Carbon::parse($tgl)->format($format) : '';
    }
}
