<?php

namespace App\Services\Ralan;

use App\Models\Dokter;
use App\Models\RegPeriksa;
use App\Models\RmeResumeRalan;
use App\Support\TindakLanjutResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ResumeRalanService
{
    public function __construct(
        private ResumeRalanBuilder $builder,
        private TindakLanjutResolver $resolver,
        private RincianBiayaRalan $biaya,
    ) {}

    public function susunOtomatis(string $noRawat): string
    {
        $meta = RmeResumeRalan::find($noRawat);
        if ($meta && $meta->status === 'diverifikasi') {
            return 'terverifikasi';
        }

        $hasil = $this->builder->susun($noRawat, $meta);
        if (!$hasil) {
            return 'tanpa_soap';
        }

        return $this->simpanOtomatis($noRawat, $meta, $hasil) ? 'disusun' : 'tetap';
    }

    public function dataTab(string $noRawat, ?string $kdDokter, bool $pakaiTerbaru = false): array
    {
        $meta = RmeResumeRalan::find($noRawat);
        $terbaru = $this->builder->susun($noRawat, $meta);

        if ($terbaru && (!$meta || $meta->status === 'otomatis')) {
            $this->simpanOtomatis($noRawat, $meta, $terbaru);
            $meta = RmeResumeRalan::find($noRawat);
        }

        $diverifikasi = $meta && $meta->status === 'diverifikasi';
        $form = $diverifikasi && !$pakaiTerbaru ? $this->formTersimpan($noRawat, $meta) : $terbaru['form'] ?? null;
        if ($diverifikasi && $pakaiTerbaru && $form) {
            // Prosedur hanya diisi dokter; data terbaru dari sumber tidak menyentuhnya.
            $form['prosedur'] = $this->formTersimpan($noRawat, $meta)['prosedur'];
        }

        $kdPemeriksa = DB::table('reg_periksa')->where('no_rawat', $noRawat)->value('kd_dokter');

        return [
            'noRawat'         => $noRawat,
            'status'          => $meta?->status,
            'form'            => $form,
            'adaDataBaru'     => $diverifikasi && !$pakaiTerbaru && $terbaru && $terbaru['sidik'] !== $meta->sidik_sumber,
            'pakaiTerbaru'    => $diverifikasi && $pakaiTerbaru,
            'bolehVerifikasi' => $kdDokter !== null && $kdDokter !== '' && $kdDokter === $kdPemeriksa,
            'verifikasi'      => $diverifikasi ? [
                'nama' => $meta->verifikator?->nm_dokter ?? $meta->diverifikasi_oleh,
                'pada' => $meta->diverifikasi_pada,
            ] : null,
            'usulanProsedur'  => $terbaru['usulan_prosedur'] ?? [],
        ];
    }

    public function verifikasi(string $noRawat, array $data, string $kdDokter): void
    {
        if (DB::table('reg_periksa')->where('no_rawat', $noRawat)->value('kd_dokter') !== $kdDokter) {
            throw new AuthorizationException('Hanya dokter pemeriksa yang dapat memverifikasi resume ini.');
        }

        $meta = RmeResumeRalan::find($noRawat);
        $tl = $this->resolver->tentukan($noRawat, null);
        if (!$tl['terkunci']) {
            $nilai = in_array($data['tindak_lanjut'] ?? '', TindakLanjutResolver::PILIHAN_MANUAL, true) ? $data['tindak_lanjut'] : 'pulang';
            $tl = [
                'nilai'       => $nilai,
                'tgl_kontrol' => $nilai === 'kontrol' ? ($data['tgl_kontrol'] ?? null) : null,
                'ket'         => in_array($nilai, ['dirujuk', 'aps'], true) ? ($data['ket_tindak_lanjut'] ?? null) : null,
            ];
        }

        $isi = ResumeRalanBuilder::isiKosong();
        $isi['kd_dokter'] = $kdDokter;
        foreach (array_keys(ResumeRalanBuilder::SALINAN) as $kolom) {
            $isi[$kolom] = trim((string) ($data[$kolom] ?? ''));
        }
        $diagnosa = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $data['diagnosa'] ?? []), 'strlen'));
        foreach (ResumeRalanBuilder::kolomDiagnosa() as $i => $kolom) {
            $isi[$kolom] = $diagnosa[$i] ?? '';
        }
        $prosedur = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $data['prosedur'] ?? []), 'strlen'));
        foreach (ResumeRalanBuilder::kolomProsedur() as $i => $kolom) {
            $isi[$kolom] = $prosedur[$i] ?? '';
        }
        $isi['kondisi_pulang'] = $tl['nilai'] === 'meninggal' ? 'Meninggal' : 'Hidup';

        DB::transaction(function () use ($noRawat, $isi, $tl, $meta, $kdDokter, $data) {
            DB::table('resume_pasien')->updateOrInsert(['no_rawat' => $noRawat], $isi);
            RmeResumeRalan::updateOrCreate(['no_rawat' => $noRawat], [
                'status'            => 'diverifikasi',
                'tindak_lanjut'     => $tl['nilai'],
                'tgl_kontrol'       => $tl['tgl_kontrol'],
                'ket_tindak_lanjut' => $tl['ket'],
                'diverifikasi_oleh' => $kdDokter,
                'diverifikasi_pada' => now(),
                'dibuat_pada'       => $meta?->dibuat_pada ?? now(),
                'sidik_sumber'      => (string) $data['sidik'],
            ]);
        });
    }

    public function dataPdf(string $noRawat): ?array
    {
        $tab = $this->dataTab($noRawat, null);
        $resume = DB::table('resume_pasien')->where('no_rawat', $noRawat)->first();
        if (!$resume) {
            return null;
        }

        $meta = RmeResumeRalan::find($noRawat);
        $reg = RegPeriksa::with(['pasien', 'poliklinik', 'dokter'])->where('no_rawat', $noRawat)->first();
        $r = $resume;

        // Kode dari casemix (diagnosa_pasien/prosedur_pasien), biasanya diisi setelah pasien pulang; kosong = kolom kosong.
        $kodeDx = DB::table('diagnosa_pasien')->where('no_rawat', $noRawat)->where('status', 'Ralan')
            ->orderBy('prioritas')->pluck('kd_penyakit')->all();
        $kodePr = DB::table('prosedur_pasien')->where('no_rawat', $noRawat)->where('status', 'Ralan')
            ->orderBy('prioritas')->pluck('kode')->all();

        $tl = $this->resolver->tentukan($noRawat, $meta);
        $prosedur = array_filter([$r->prosedur_utama, $r->prosedur_sekunder, $r->prosedur_sekunder2, $r->prosedur_sekunder3], 'strlen');
        // Tindak lanjut "pulang" biasa tidak ditulis; kontrol, rujuk, APS, rawat inap, meninggal ditaruh di akhir terapi.
        $terapi = array_filter([implode("\n", $prosedur), trim((string) $r->obat_pulang), $tl['nilai'] === 'pulang' ? '' : $tl['label']], 'strlen');

        return [
            'reg'           => $reg,
            'resume'        => $resume,
            'diverifikasi'  => $tab['status'] === 'diverifikasi',
            'umur'          => trim($reg->umurdaftar . ' ' . (['Th' => 'thn', 'Bl' => 'bln', 'Hr' => 'hr'][$reg->sttsumur] ?? $reg->sttsumur)),
            'anamnesa'      => trim(implode("\n", array_filter([trim((string) $r->keluhan_utama), trim((string) $r->jalannya_penyakit)], 'strlen'))),
            'penunjang'     => trim(implode("\n", array_filter([trim((string) $r->hasil_laborat), trim((string) $r->pemeriksaan_penunjang)], 'strlen'))),
            'ttv'           => $this->ttvTerakhir($noRawat),
            'dxSekunder'    => array_values(array_filter([$r->diagnosa_sekunder, $r->diagnosa_sekunder2, $r->diagnosa_sekunder3, $r->diagnosa_sekunder4], 'strlen')),
            'kodePrimer'    => $kodeDx[0] ?? '',
            'kodeSekunder'  => implode(', ', array_slice($kodeDx, 1)),
            'kodeProsedur'  => implode(', ', $kodePr),
            'terapi'        => implode("\n", $terapi),
            'biaya'         => $this->biaya->untuk($noRawat),
            'dokterTtd'     => $tab['status'] === 'diverifikasi' ? Dokter::find($meta->diverifikasi_oleh) : null,
        ];
    }

    /** Tensi/nadi/SpO2 pemeriksaan terakhir yang mengisi salah satunya; isian kosong menjadi "-". */
    private function ttvTerakhir(string $noRawat): array
    {
        $kosong = fn ($v) => in_array(trim((string) $v), ['', '-'], true);
        $baris = DB::table('pemeriksaan_ralan')->where('no_rawat', $noRawat)
            ->orderByDesc('tgl_perawatan')->orderByDesc('jam_rawat')->get(['tensi', 'nadi', 'spo2'])
            ->first(fn ($p) => !$kosong($p->tensi) || !$kosong($p->nadi) || !$kosong($p->spo2));

        $nilai = fn ($v, $satuan) => $baris && !$kosong($v) ? trim($v) . $satuan : '-';

        return [
            'tensi' => $nilai($baris->tensi ?? null, ' mmHg'),
            'nadi'  => $nilai($baris->nadi ?? null, ' x/mnt'),
            'spo2'  => $nilai($baris->spo2 ?? null, '%'),
        ];
    }

    /** @return bool true bila menulis */
    private function simpanOtomatis(string $noRawat, ?RmeResumeRalan $meta, array $hasil): bool
    {
        if ($meta && $meta->sidik_sumber === $hasil['sidik']
            && DB::table('resume_pasien')->where('no_rawat', $noRawat)->exists()) {
            return false;
        }

        DB::transaction(function () use ($noRawat, $meta, $hasil) {
            DB::table('resume_pasien')->updateOrInsert(['no_rawat' => $noRawat], $hasil['isi']);
            RmeResumeRalan::updateOrCreate(['no_rawat' => $noRawat], [
                'status'            => 'otomatis',
                'tindak_lanjut'     => $hasil['form']['tindak_lanjut']['nilai'],
                'tgl_kontrol'       => $meta?->tgl_kontrol,
                'ket_tindak_lanjut' => $meta?->ket_tindak_lanjut,
                'dibuat_pada'       => now(),
                'sidik_sumber'      => $hasil['sidik'],
            ]);
        });

        return true;
    }

    private function formTersimpan(string $noRawat, RmeResumeRalan $meta): array
    {
        $r = (array) DB::table('resume_pasien')->where('no_rawat', $noRawat)->first();
        $ambil = fn (array $kolom) => array_values(array_filter(array_map(fn ($k) => (string) ($r[$k] ?? ''), $kolom), 'strlen'));
        $salinan = array_keys(ResumeRalanBuilder::SALINAN);

        return [
            'diagnosa'      => $ambil(ResumeRalanBuilder::kolomDiagnosa()),
            'prosedur'      => $ambil(ResumeRalanBuilder::kolomProsedur()),
            'salinan'       => array_combine($salinan, array_map(fn ($k) => (string) ($r[$k] ?? ''), $salinan)),
            'tindak_lanjut' => $this->resolver->tentukan($noRawat, $meta),
            'sidik'         => $meta->sidik_sumber,
        ];
    }
}
