<?php

namespace App\Services\Ralan;

use App\Models\Dokter;
use App\Models\RegPeriksa;
use App\Models\RmeKfr;
use App\Support\KfrFormat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Formulir Rehab Medik (KFR) kunjungan ralan poli P003: Formulir Asesmen Layanan KFR
 * (layanan_kedokteran_fisik_rehabilitasi) dan Lembar Hasil Uji Fungsi (uji_fungsi_kfr),
 * ditambah rme_kfr untuk isian kertas yang tidak punya kolom Khanza.
 * Anamnesa, pemeriksaan, diagnosis medis, tatalaksana, dan anjuran diambil dari SOAP.
 */
class KfrService
{
    public const POLI = 'P003';

    private const LAYANAN = 'layanan_kedokteran_fisik_rehabilitasi';
    private const UJI = 'uji_fungsi_kfr';

    /** Kolom Khanza yang bisa diubah di Khanza desktop dan tidak ditimpa saat simpan ulang. */
    private const LAYANAN_BAWAAN = [
        'pendamping' => 'Tidak', 'keterangan_pendamping' => '', 'evaluasi' => '', 'status_program' => 'Belum Selesai',
    ];

    private const ISIAN_KOSONG = [
        'diagnosa_fungsi' => '', 'diagnosis_fungsional' => '', 'penunjang' => '', 'target' => '', 'edukasi' => '',
        'suspek' => 'Tidak', 'ket_suspek' => '', 'kesimpulan' => '', 'rekomendasi' => '',
    ];

    public function isKfr(string $noRawat): bool
    {
        return DB::table('reg_periksa')->where('no_rawat', $noRawat)->value('kd_poli') === self::POLI;
    }

    /**
     * @return array{isian: array<string,string>, sumber: string, tglSumber: ?string, disimpanPada: ?string}
     *   sumber: 'tersimpan' (kunjungan ini), 'kunjungan' (kunjungan KFR sebelumnya), 'kosong'.
     */
    public function isianAwal(string $noRawat): array
    {
        $tersimpan = RmeKfr::find($noRawat);
        if ($tersimpan) {
            return [
                'isian' => $this->bacaIsian($noRawat), 'sumber' => 'tersimpan', 'tglSumber' => null,
                'disimpanPada' => $tersimpan->disimpan_pada?->format('Y-m-d H:i:s'),
            ];
        }

        $lama = $this->kunjunganSebelumnya($noRawat);
        if ($lama) {
            return ['isian' => $this->bacaIsian($lama->no_rawat), 'sumber' => 'kunjungan', 'tglSumber' => $lama->tgl_registrasi, 'disimpanPada' => null];
        }

        return ['isian' => self::ISIAN_KOSONG, 'sumber' => 'kosong', 'tglSumber' => null, 'disimpanPada' => null];
    }

    /** Dipanggil setelah SOAP disimpan, di transaksi yang sama. $isian sudah divalidasi (BatasIsianKfr). */
    public function simpan(string $noRawat, array $isian, string $kdDokter): void
    {
        $isian = array_map(fn ($v) => trim((string) $v), array_merge(self::ISIAN_KOSONG, array_intersect_key($isian, self::ISIAN_KOSONG)));
        $suspek = $isian['suspek'] === 'Ya' ? 'Ya' : 'Tidak';
        $soap = $this->soapTerakhir($noRawat);
        $s = fn (string $kolom) => trim((string) ($soap->{$kolom} ?? ''));
        $instruksi = KfrFormat::pecahInstruksi($soap->instruksi ?? '');
        $anjuran = $instruksi['durasi'] === '' ? $instruksi['frekuensi'] : $instruksi['frekuensi'] . ', ' . $instruksi['durasi'];
        $sekarang = now()->format('Y-m-d H:i:s');

        $this->upsert(self::LAYANAN, $noRawat, [
            'tanggal'                          => $sekarang,
            'kd_dokter'                        => $kdDokter,
            'anamnesa'                         => KfrFormat::potong($s('keluhan'), 500),
            'pemeriksaan_fisik'                => KfrFormat::potong($s('pemeriksaan'), 1500),
            'diagnosa_medis'                   => KfrFormat::potong($s('penilaian'), 200),
            'diagnosa_fungsi'                  => $isian['diagnosa_fungsi'],
            'tatalaksana'                      => KfrFormat::potong($s('rtl'), 2000),
            'anjuran'                          => KfrFormat::potong($anjuran, 500),
            'suspek_penyakit_kerja'            => $suspek,
            'keterangan_suspek_penyakit_kerja' => $suspek === 'Ya' ? $isian['ket_suspek'] : '',
        ], self::LAYANAN_BAWAAN);

        $this->upsert(self::UJI, $noRawat, [
            'tanggal'              => $sekarang,
            'kd_dokter'            => $kdDokter,
            'diagnosis_fungsional' => $isian['diagnosis_fungsional'],
            'diagnosis_medis'      => KfrFormat::potong($s('penilaian'), 50),
            'hasil_didapat'        => KfrFormat::potong($s('pemeriksaan'), 100),
            'kesimpulan'           => $isian['kesimpulan'],
            'rekomedasi'           => $isian['rekomendasi'],
        ]);

        RmeKfr::updateOrCreate(['no_rawat' => $noRawat], [
            'penunjang' => $isian['penunjang'], 'target' => $isian['target'], 'edukasi' => $isian['edukasi'],
            'disimpan_oleh' => $kdDokter, 'disimpan_pada' => $sekarang,
        ]);
    }

    /**
     * Data dua PDF KFR, null bila formulir belum pernah disimpan. Isi klinis dibaca dari SOAP saat cetak
     * (utuh, termasuk perubahan sesudah simpan), bukan dari kolom Khanza yang sudah dipotong.
     */
    public function dataPdf(string $noRawat): ?array
    {
        if (!RmeKfr::find($noRawat)) {
            return null;
        }

        $reg = RegPeriksa::with('pasien')->where('no_rawat', $noRawat)->first();
        $kdDokter = DB::table(self::LAYANAN)->where('no_rawat', $noRawat)->value('kd_dokter') ?: $reg->kd_dokter;
        $dokter = Dokter::find($kdDokter);
        $soap = $this->soapTerakhir($noRawat);
        $instruksi = KfrFormat::pecahInstruksi($soap->instruksi ?? '');
        $tgl = Carbon::parse($reg->tgl_registrasi);

        // Kode dari casemix; kosong sampai casemix mengode kunjungan ini.
        $kodeDx = DB::table('diagnosa_pasien')->where('no_rawat', $noRawat)->where('status', 'Ralan')
            ->orderBy('prioritas')->pluck('kd_penyakit')->implode(', ');
        $kodePr = DB::table('prosedur_pasien')->where('no_rawat', $noRawat)->where('status', 'Ralan')
            ->orderBy('prioritas')->pluck('kode')->implode(', ');

        return [
            'reg'          => $reg,
            'umur'         => trim($reg->umurdaftar . ' ' . (['Th' => 'Tahun', 'Bl' => 'Bulan', 'Hr' => 'Hari'][$reg->sttsumur] ?? $reg->sttsumur)),
            'tglPelayanan' => $tgl->format('d/m/Y'),
            'tglPendek'    => $tgl->format('d/m/y'),
            'soap'         => [
                'anamnesa'    => trim((string) ($soap->keluhan ?? '')),
                'pemeriksaan' => trim((string) ($soap->pemeriksaan ?? '')),
                'diagnosis'   => trim((string) ($soap->penilaian ?? '')),
                'tatalaksana' => trim((string) ($soap->rtl ?? '')),
            ],
            'frekuensi'    => $instruksi['frekuensi'],
            'durasi'       => $instruksi['durasi'],
            'kfr'          => $this->bacaIsian($noRawat),
            'kodeDx'       => $kodeDx,
            'kodePr'       => $kodePr,
            'dokter'       => $dokter,
            'sip'          => trim((string) ($dokter->no_ijn_praktek ?? '')),
        ];
    }

    /** Baris pemeriksaan_ralan kunjungan ini (PK no_rawat: satu baris per kunjungan). */
    public function soapTerakhir(string $noRawat): ?object
    {
        return DB::table('pemeriksaan_ralan')->where('no_rawat', $noRawat)
            ->orderByDesc('tgl_perawatan')->orderByDesc('jam_rawat')->first();
    }

    private function upsert(string $tabel, string $noRawat, array $isi, array $bawaanInsert = []): void
    {
        $query = DB::table($tabel)->where('no_rawat', $noRawat);
        if ($query->exists()) {
            $query->update($isi);
        } else {
            DB::table($tabel)->insert(['no_rawat' => $noRawat] + $isi + $bawaanInsert);
        }
    }

    private function bacaIsian(string $noRawat): array
    {
        $l = DB::table(self::LAYANAN)->where('no_rawat', $noRawat)->first();
        $u = DB::table(self::UJI)->where('no_rawat', $noRawat)->first();
        $r = RmeKfr::find($noRawat);
        $t = fn ($baris, string $kolom) => (string) ($baris->{$kolom} ?? '');

        return [
            'diagnosa_fungsi'      => $t($l, 'diagnosa_fungsi'),
            'diagnosis_fungsional' => $t($u, 'diagnosis_fungsional'),
            'penunjang'            => $t($r, 'penunjang'),
            'target'               => $t($r, 'target'),
            'edukasi'              => $t($r, 'edukasi'),
            'suspek'               => $t($l, 'suspek_penyakit_kerja') === 'Ya' ? 'Ya' : 'Tidak',
            'ket_suspek'           => $t($l, 'keterangan_suspek_penyakit_kerja'),
            'kesimpulan'           => $t($u, 'kesimpulan'),
            'rekomendasi'          => $t($u, 'rekomedasi'),
        ];
    }

    /** Kunjungan P003 terakhir sebelum kunjungan ini, milik pasien yang sama, yang punya formulir KFR. */
    private function kunjunganSebelumnya(string $noRawat): ?object
    {
        $kini = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first(['no_rkm_medis', 'tgl_registrasi', 'jam_reg']);
        if (!$kini) {
            return null;
        }

        return DB::table('reg_periksa as r')
            ->where('r.no_rkm_medis', $kini->no_rkm_medis)
            ->where('r.kd_poli', self::POLI)
            ->where('r.no_rawat', '!=', $noRawat)
            ->where(fn ($q) => $q->where('r.tgl_registrasi', '<', $kini->tgl_registrasi)
                ->orWhere(fn ($q) => $q->where('r.tgl_registrasi', $kini->tgl_registrasi)->where('r.jam_reg', '<', $kini->jam_reg)))
            ->where(fn ($q) => $q->whereExists(fn ($s) => $s->from('rme_kfr')->whereColumn('rme_kfr.no_rawat', 'r.no_rawat'))
                ->orWhereExists(fn ($s) => $s->from(self::LAYANAN)->whereColumn(self::LAYANAN . '.no_rawat', 'r.no_rawat'))
                ->orWhereExists(fn ($s) => $s->from(self::UJI)->whereColumn(self::UJI . '.no_rawat', 'r.no_rawat')))
            ->orderByDesc('r.tgl_registrasi')->orderByDesc('r.jam_reg')
            ->first(['r.no_rawat', 'r.tgl_registrasi']);
    }
}
