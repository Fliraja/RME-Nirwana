<?php

namespace App\Services\Ralan;

use App\Models\RmeResumeRalan;
use App\Support\DiagnosaSplitter;
use App\Support\ResumeFormat;
use App\Support\TindakLanjutResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ResumeRalanBuilder
{
    public const SALINAN = [
        'keluhan_utama'         => 'Keluhan utama',
        'jalannya_penyakit'     => 'Pemeriksaan',
        'hasil_laborat'         => 'Hasil laboratorium',
        'pemeriksaan_penunjang' => 'Penunjang lain',
        'obat_pulang'           => 'Obat pulang',
    ];

    private const KOLOM_DIAGNOSA = ['diagnosa_utama', 'diagnosa_sekunder', 'diagnosa_sekunder2', 'diagnosa_sekunder3', 'diagnosa_sekunder4'];
    private const KOLOM_PROSEDUR = ['prosedur_utama', 'prosedur_sekunder', 'prosedur_sekunder2', 'prosedur_sekunder3'];

    /** Tagihan yang bukan prosedur medis (jasa, konsultasi, askep, bahan, administrasi). */
    private const BUKAN_PROSEDUR = '/konsul|jasa|askep|bakhp|observasi|telemedicine|administrasi/i';

    public function __construct(private TindakLanjutResolver $resolver) {}

    public function susun(string $noRawat, ?RmeResumeRalan $meta = null): ?array
    {
        $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first(['kd_dokter', 'stts']);
        if (!$reg || $reg->stts === 'Batal') {
            return null;
        }

        $soap = DB::table('pemeriksaan_ralan')->where('no_rawat', $noRawat)
            ->whereRaw("TRIM(COALESCE(penilaian, '')) NOT IN ('', '-')")
            ->orderByDesc('tgl_perawatan')->orderByDesc('jam_rawat')->first();
        if (!$soap) {
            return null;
        }

        $ttv = ResumeFormat::ttv($soap);
        $diagnosa = DiagnosaSplitter::pecah($soap->penilaian);
        $tl = $this->resolver->tentukan($noRawat, $meta);

        $salinan = [
            'keluhan_utama'         => trim((string) $soap->keluhan),
            'jalannya_penyakit'     => trim(implode("\n", array_filter([trim((string) $soap->pemeriksaan), $ttv]))),
            'hasil_laborat'         => $this->lab($noRawat),
            'pemeriksaan_penunjang' => $this->radiologi($noRawat),
            'obat_pulang'           => $this->obat($noRawat),
        ];

        $isi = self::isiKosong();
        $isi['kd_dokter'] = (string) $reg->kd_dokter;
        foreach ($salinan as $kolom => $teks) {
            $isi[$kolom] = $teks;
        }
        $daftarDiagnosa = array_values(array_filter(array_merge([$diagnosa['utama']], $diagnosa['sekunder'])));
        foreach (self::KOLOM_DIAGNOSA as $i => $kolom) {
            $isi[$kolom] = $daftarDiagnosa[$i] ?? '';
        }
        $isi['kondisi_pulang'] = $tl['nilai'] === 'meninggal' ? 'Meninggal' : 'Hidup';

        $sidik = sha1(json_encode([
            $salinan, $soap->penilaian,
            [$tl['nilai'], $tl['terkunci'], $tl['tgl_kontrol'], $tl['ket']],
        ]));

        return [
            'isi'             => $isi,
            'form'            => [
                'diagnosa'      => $daftarDiagnosa,
                'prosedur'      => [],
                'salinan'       => $salinan,
                'tindak_lanjut' => $tl,
                'sidik'         => $sidik,
            ],
            'usulan_prosedur' => $this->usulanProsedur($noRawat),
            'sidik'           => $sidik,
        ];
    }

    /** @return array<string,string> */
    public static function isiKosong(): array
    {
        $kolom = array_merge(
            array_keys(self::SALINAN), self::KOLOM_DIAGNOSA, self::KOLOM_PROSEDUR,
            ['kd_diagnosa_utama', 'kd_diagnosa_sekunder', 'kd_diagnosa_sekunder2', 'kd_diagnosa_sekunder3', 'kd_diagnosa_sekunder4',
             'kd_prosedur_utama', 'kd_prosedur_sekunder', 'kd_prosedur_sekunder2', 'kd_prosedur_sekunder3', 'kondisi_pulang']
        );

        return array_fill_keys($kolom, '');
    }

    /** @return list<string> */
    public static function kolomDiagnosa(): array
    {
        return self::KOLOM_DIAGNOSA;
    }

    /** @return list<string> */
    public static function kolomProsedur(): array
    {
        return self::KOLOM_PROSEDUR;
    }

    private function lab(string $noRawat): string
    {
        return DB::table('detail_periksa_lab as d')
            ->join('template_laboratorium as t', function ($j) {
                $j->on('t.id_template', '=', 'd.id_template')->on('t.kd_jenis_prw', '=', 'd.kd_jenis_prw');
            })
            ->where('d.no_rawat', $noRawat)
            ->orderBy('d.tgl_periksa')->orderBy('d.jam')->orderBy('t.urut')
            ->get(['t.Pemeriksaan', 'd.nilai', 't.satuan', 'd.nilai_rujukan'])
            ->filter(fn ($r) => trim((string) $r->nilai) !== '')
            ->map(fn ($r) => ResumeFormat::barisLab((string) $r->Pemeriksaan, (string) $r->nilai, (string) $r->satuan, (string) $r->nilai_rujukan))
            ->implode("\n");
    }

    private function radiologi(string $noRawat): string
    {
        return DB::table('hasil_radiologi')->where('no_rawat', $noRawat)
            ->orderBy('tgl_periksa')->orderBy('jam')->get(['tgl_periksa', 'hasil'])
            ->filter(fn ($r) => trim((string) $r->hasil) !== '')
            ->map(fn ($r) => Carbon::parse($r->tgl_periksa)->translatedFormat('d F Y') . ': ' . trim($r->hasil))
            ->implode("\n");
    }

    private function obat(string $noRawat): string
    {
        $resep = DB::table('resep_obat')->where('no_rawat', $noRawat)->where('status', 'ralan')->pluck('no_resep');
        if ($resep->isEmpty()) {
            return '';
        }

        $jadi = DB::table('resep_dokter as r')->join('databarang as b', 'b.kode_brng', '=', 'r.kode_brng')
            ->whereIn('r.no_resep', $resep)->orderBy('b.nama_brng')
            ->get(['b.nama_brng', 'r.jml', 'r.aturan_pakai'])
            ->map(fn ($r) => ResumeFormat::barisObat($r->nama_brng, self::jumlah($r->jml), (string) $r->aturan_pakai));

        $racik = DB::table('resep_dokter_racikan')->whereIn('no_resep', $resep)->orderBy('no_racik')
            ->get(['nama_racik', 'jml_dr', 'aturan_pakai'])
            ->map(fn ($r) => ResumeFormat::barisObat($r->nama_racik, self::jumlah($r->jml_dr), (string) $r->aturan_pakai));

        return $jadi->merge($racik)->implode("\n");
    }

    /** @return list<string> */
    private function usulanProsedur(string $noRawat): array
    {
        $tagihan = DB::table('rawat_jl_dr')->where('no_rawat', $noRawat)->select('kd_jenis_prw')
            ->union(DB::table('rawat_jl_drpr')->where('no_rawat', $noRawat)->select('kd_jenis_prw'));

        return DB::table('jns_perawatan as j')
            ->joinSub($tagihan, 't', 't.kd_jenis_prw', '=', 'j.kd_jenis_prw')
            ->leftJoin('kategori_perawatan as k', 'k.kd_kategori', '=', 'j.kd_kategori')
            ->whereNotNull('k.nm_kategori')->whereNotIn('k.nm_kategori', ['-', 'Administrasi'])
            ->pluck('j.nm_perawatan')
            ->reject(fn ($nama) => preg_match(self::BUKAN_PROSEDUR, $nama))
            ->map(fn ($nama) => mb_substr(trim($nama), 0, DiagnosaSplitter::MAKS_KARAKTER))
            ->unique()->values()->all();
    }

    private static function jumlah($angka): string
    {
        $angka = (float) $angka;

        return $angka == (int) $angka ? (string) (int) $angka : rtrim(rtrim(number_format($angka, 2, ',', ''), '0'), ',');
    }
}
