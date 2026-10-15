<?php

namespace App\Services\Ranap;

use App\Models\DiagnosaPasien;
use App\Models\Dokter;
use App\Models\ProsedurPasien;
use App\Models\RegPeriksa;
use App\Models\ResumePasienRanap;
use App\Support\Alergi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RanapResumeService
{
    public function __construct(
        private RanapPenunjangService $penunjangService
    ) {}

    /**
     * Ambil data resume pasien atau persiapkan auto-fill awal jika belum ada
     */
    public function getResumeData(string $noRawat): array
    {
        $resume = ResumePasienRanap::with('dokter')->where('no_rawat', $noRawat)->first();

        // Data pendukung dari rekam medis ranap
        $reg = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->where('reg_periksa.no_rawat', $noRawat)
            ->select('reg_periksa.*', 'pasien.nm_pasien')
            ->first();

        $alergiPasien = Alergi::terakhirRanap($noRawat) ?? '';

        $kamar = DB::table('kamar_inap')
            ->where('no_rawat', $noRawat)
            ->orderBy('tgl_masuk', 'asc')
            ->first();

        $dpjp = DB::table('dpjp_ranap')
            ->join('dokter', 'dpjp_ranap.kd_dokter', '=', 'dokter.kd_dokter')
            ->where('dpjp_ranap.no_rawat', $noRawat)
            ->select('dokter.kd_dokter', 'dokter.nm_dokter')
            ->first();

        if (!$dpjp) {
            $dpjp = DB::table('reg_periksa')
                ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
                ->where('reg_periksa.no_rawat', $noRawat)
                ->select('dokter.kd_dokter', 'dokter.nm_dokter')
                ->first();
        }

        $dokterList = Dokter::where('status', '1')->orderBy('nm_dokter', 'asc')->get(['kd_dokter', 'nm_dokter']);

        // Data awal diagnosa ICD-10 yang sudah dientri di ranap
        $diagnosaList = DiagnosaPasien::with('penyakit')
            ->where('no_rawat', $noRawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        // Data awal prosedur ICD-9
        $prosedurList = ProsedurPasien::with('icd9')
            ->where('no_rawat', $noRawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        // Ambil riwayat SOAP CPPT (hanya baris yang terisi SOAP, bukan baris TTV kosong)
        $latestKeluhan = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->whereNotNull('keluhan')
            ->where('keluhan', '!=', '')
            ->where('keluhan', '!=', '-')
            ->orderBy('tgl_perawatan', 'desc')
            ->orderBy('jam_rawat', 'desc')
            ->value('keluhan') ?? '';

        $latestPemeriksaan = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->whereNotNull('pemeriksaan')
            ->where('pemeriksaan', '!=', '')
            ->where('pemeriksaan', '!=', '-')
            ->orderBy('tgl_perawatan', 'desc')
            ->orderBy('jam_rawat', 'desc')
            ->value('pemeriksaan') ?? '';

        $latestRtl = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->whereNotNull('rtl')
            ->where('rtl', '!=', '')
            ->where('rtl', '!=', '-')
            ->orderBy('tgl_perawatan', 'desc')
            ->orderBy('jam_rawat', 'desc')
            ->value('rtl') ?? '';

        $latestInstruksi = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->whereNotNull('instruksi')
            ->where('instruksi', '!=', '')
            ->where('instruksi', '!=', '-')
            ->orderBy('tgl_perawatan', 'desc')
            ->orderBy('jam_rawat', 'desc')
            ->value('instruksi') ?? '';

        // Baris SOAP CPPT terakhir (yang memiliki keluhan / pemeriksaan / rtl / instruksi)
        $soapTerakhir = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->where(function ($q) {
                $q->where(function ($s) {
                    $s->whereNotNull('keluhan')
                      ->where('keluhan', '!=', '')
                      ->where('keluhan', '!=', '-');
                })
                ->orWhere(function ($s) {
                    $s->whereNotNull('pemeriksaan')
                      ->where('pemeriksaan', '!=', '')
                      ->where('pemeriksaan', '!=', '-');
                })
                ->orWhere(function ($s) {
                    $s->whereNotNull('rtl')
                      ->where('rtl', '!=', '')
                      ->where('rtl', '!=', '-');
                })
                ->orWhere(function ($s) {
                    $s->whereNotNull('instruksi')
                      ->where('instruksi', '!=', '')
                      ->where('instruksi', '!=', '-');
                });
            })
            ->orderBy('tgl_perawatan', 'desc')
            ->orderBy('jam_rawat', 'desc')
            ->first();

        // SOAP pertama saat MRS
        $soapAwal = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->where(function ($q) {
                $q->where(function ($s) {
                    $s->whereNotNull('keluhan')
                      ->where('keluhan', '!=', '')
                      ->where('keluhan', '!=', '-');
                })
                ->orWhere(function ($s) {
                    $s->whereNotNull('pemeriksaan')
                      ->where('pemeriksaan', '!=', '')
                      ->where('pemeriksaan', '!=', '-');
                });
            })
            ->orderBy('tgl_perawatan', 'asc')
            ->orderBy('jam_rawat', 'asc')
            ->first();

        // Daftar obat selama di rawat inap
        $obatRsList = DB::table('detail_pemberian_obat')
            ->join('databarang', 'detail_pemberian_obat.kode_brng', '=', 'databarang.kode_brng')
            ->where('detail_pemberian_obat.no_rawat', $noRawat)
            ->select('databarang.nama_brng')
            ->distinct()
            ->pluck('nama_brng')
            ->toArray();

        // Ringkasan hasil lab dan radiologi terbaru untuk pemeriksaan penunjang
        $ringkasanPenunjang = $this->getRingkasanPenunjangTerbaru($noRawat);

        return compact(
            'resume',
            'reg',
            'alergiPasien',
            'kamar',
            'dpjp',
            'dokterList',
            'diagnosaList',
            'prosedurList',
            'soapTerakhir',
            'soapAwal',
            'latestKeluhan',
            'latestPemeriksaan',
            'latestRtl',
            'latestInstruksi',
            'obatRsList',
            'ringkasanPenunjang'
        );
    }

    /**
     * Data lembar cetak resume: hanya identitas, kamar, DPJP, dan isi resume tersimpan.
     */
    public function getPdfData(string $noRawat): ?array
    {
        $resume = ResumePasienRanap::with('dokter')->find($noRawat);
        if (!$resume) {
            return null;
        }

        $reg = RegPeriksa::with([
                'kamarInap' => fn ($q) => $q->orderBy('tgl_masuk')->orderBy('jam_masuk'),
                'kamarInap.kamar.bangsal', 'pasien', 'penjab', 'dokter', 'dpjpRanap.dokter',
            ])
            ->where('no_rawat', $noRawat)
            ->first();

        $kamarList = $reg?->kamarInap ?? collect();

        return [
            'resume'       => $resume,
            'reg'          => $reg,
            'kamarMasuk'   => $kamarList->first(),
            'kamarAkhir'   => $kamarList->last(),
            'lamaRawat'    => (int) $kamarList->sum('lama'),
            'dpjp'         => $reg?->dpjpRanap->first()?->dokter ?? $reg?->dokter,
            'alergiPasien' => Alergi::terakhirRanap($noRawat),
        ];
    }

    /**
     * Ambil dan format hasil laboratorium & bacaan radiologi terbaru
     */
    public function getRingkasanPenunjangTerbaru(string $noRawat): string
    {
        $parts = [];

        // 1. Hasil Laboratorium Terbaru
        $hasilLab = $this->penunjangService->getHasilLab($noRawat);
        if (!empty($hasilLab)) {
            $labSections = [];
            $count = 0;
            foreach ($hasilLab as $key => $g) {
                if ($count >= 5) break; // Maksimal 5 paket lab terbaru
                $validItems = array_filter($g['items'], function ($item) {
                    return !empty($item['nilai']) && $item['nilai'] !== '-' && !empty($item['pemeriksaan']);
                });

                if (!empty($validItems)) {
                    $lines = ["LAB: {$g['nm_perawatan']} ({$g['tgl_periksa']} {$g['jam']})"];
                    foreach ($validItems as $it) {
                        $satuan = !empty($it['satuan']) ? ' ' . $it['satuan'] : '';
                        $ref = !empty($it['nilai_rujukan']) ? " (Ruj: {$it['nilai_rujukan']})" : '';
                        $lines[] = "  • {$it['pemeriksaan']}: {$it['nilai']}{$satuan}{$ref}";
                    }
                    $labSections[] = implode("\n", $lines);
                    $count++;
                }
            }

            if (!empty($labSections)) {
                $parts[] = "[HASIL LABORATORIUM TERBARU]\n" . implode("\n\n", $labSections);
            }
        }

        // 2. Hasil Radiologi (Ekspertise) Terbaru
        $hasilRad = $this->penunjangService->getHasilRadiologi($noRawat);
        if (!empty($hasilRad['expertise']) && count($hasilRad['expertise']) > 0) {
            $radSections = [];
            foreach ($hasilRad['expertise'] as $exp) {
                $radSections[] = "RADIOLOGI ({$exp->tgl_periksa} {$exp->jam}):\n" . trim($exp->hasil);
            }
            $parts[] = "[HASIL BACAAN RADIOLOGI TERBARU]\n" . implode("\n\n", $radSections);
        } else {
            // Jika belum ada teks ekspertise, tampilkan tindakan radiologi yang sudah dikerjakan
            $periksaRad = DB::table('periksa_radiologi')
                ->join('jns_perawatan_radiologi', 'periksa_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                ->where('periksa_radiologi.no_rawat', $noRawat)
                ->orderBy('periksa_radiologi.tgl_periksa', 'desc')
                ->orderBy('periksa_radiologi.jam', 'desc')
                ->select('jns_perawatan_radiologi.nm_perawatan', 'periksa_radiologi.tgl_periksa')
                ->get();

            if ($periksaRad->isNotEmpty()) {
                $radItems = $periksaRad->map(fn($r) => "  • {$r->nm_perawatan} ({$r->tgl_periksa})")->implode("\n");
                $parts[] = "[TINDAKAN RADIOLOGI TERDAFTAR]\n" . $radItems;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Simpan / perbarui resume pasien ranap
     */
    public function simpan(array $data, ?string $defaultKdDokter = null): array
    {
        $noRawat = $data['no_rawat'] ?? '';
        if (empty($noRawat)) {
            return ['status' => 'error', 'message' => 'Nomor rawat tidak valid.'];
        }

        $kdDokter = !empty($data['kd_dokter']) ? $data['kd_dokter'] : ($defaultKdDokter ?: '-');

        // Pemetaan kondisi keluar berdasarkan opsi yang dipilih dokter
        $kondisiOpsi = $data['kondisi_pulang_opsi'] ?? 'sembuh';
        $caraKeluar = 'Atas Izin Dokter';
        $ketKeluar = '';
        $keadaan = 'Membaik';
        $ketKeadaan = '';
        $dilanjutkan = 'Kembali Ke RS';
        $ketDilanjutkan = '';

        switch ($kondisiOpsi) {
            case 'sembuh':
                $caraKeluar = 'Atas Izin Dokter';
                $keadaan = 'Membaik';
                $dilanjutkan = 'Kembali Ke RS';
                break;
            case 'aps':
                $caraKeluar = 'Pulang Atas Permintaan Sendiri';
                $keadaan = 'Membaik';
                $dilanjutkan = 'Lainnya';
                $ketDilanjutkan = 'APS';
                break;
            case 'meninggal_kurang_48':
                $caraKeluar = 'Lainnya';
                $keadaan = 'Meninggal';
                $ketKeadaan = '< 48 jam';
                $ketKeluar = 'Meninggal dunia < 48 jam';
                break;
            case 'meninggal_lebih_48':
                $caraKeluar = 'Lainnya';
                $keadaan = 'Meninggal';
                $ketKeadaan = '> 48 jam';
                $ketKeluar = 'Meninggal dunia > 48 jam';
                break;
            case 'dirujuk':
                $namaRujukan = trim($data['nama_faskes_rujukan'] ?? '');
                $caraKeluar = 'Pindah RS';
                $ketKeluar = $namaRujukan;
                $dilanjutkan = 'RS Lain';
                $ketDilanjutkan = $namaRujukan;
                break;
            default:
                $caraKeluar = $data['cara_keluar'] ?? 'Atas Izin Dokter';
                $keadaan = $data['keadaan'] ?? 'Membaik';
                $ketKeluar = $data['ket_keluar'] ?? '';
                $ketKeadaan = $data['ket_keadaan'] ?? '';
                $dilanjutkan = $data['dilanjutkan'] ?? 'Kembali Ke RS';
                $ketDilanjutkan = $data['ket_dilanjutkan'] ?? '';
                break;
        }

        // Format tanggal kontrol jika ada
        $kontrol = null;
        if (!empty($data['kontrol'])) {
            try {
                $kontrol = Carbon::parse($data['kontrol'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $kontrol = null;
            }
        }

        $payload = [
            'kd_dokter'              => $kdDokter,
            'diagnosa_awal'          => $data['diagnosa_awal'] ?? '',
            'alasan'                 => $data['alasan'] ?? '',
            'keluhan_utama'          => $data['keluhan_utama'] ?? '',
            'pemeriksaan_fisik'      => $data['pemeriksaan_fisik'] ?? '',
            'jalannya_penyakit'      => $data['jalannya_penyakit'] ?? '',
            'pemeriksaan_penunjang'  => $data['pemeriksaan_penunjang'] ?? '',
            'hasil_laborat'          => $data['hasil_laborat'] ?? '',
            'tindakan_dan_operasi'   => $data['tindakan_dan_operasi'] ?? '',
            'obat_di_rs'             => $data['obat_di_rs'] ?? '',
            'diagnosa_utama'         => $data['diagnosa_utama'] ?? '',
            'kd_diagnosa_utama'      => $data['kd_diagnosa_utama'] ?? '',
            'diagnosa_sekunder'      => $data['diagnosa_sekunder'] ?? '',
            'kd_diagnosa_sekunder'   => $data['kd_diagnosa_sekunder'] ?? '',
            'diagnosa_sekunder2'     => $data['diagnosa_sekunder2'] ?? '',
            'kd_diagnosa_sekunder2'  => $data['kd_diagnosa_sekunder2'] ?? '',
            'diagnosa_sekunder3'     => $data['diagnosa_sekunder3'] ?? '',
            'kd_diagnosa_sekunder3'  => $data['kd_diagnosa_sekunder3'] ?? '',
            'diagnosa_sekunder4'     => $data['diagnosa_sekunder4'] ?? '',
            'kd_diagnosa_sekunder4'  => $data['kd_diagnosa_sekunder4'] ?? '',
            'prosedur_utama'         => $data['prosedur_utama'] ?? '',
            'kd_prosedur_utama'      => $data['kd_prosedur_utama'] ?? '',
            'prosedur_sekunder'      => $data['prosedur_sekunder'] ?? '',
            'kd_prosedur_sekunder'   => $data['kd_prosedur_sekunder'] ?? '',
            'prosedur_sekunder2'     => $data['prosedur_sekunder2'] ?? '',
            'kd_prosedur_sekunder2'  => $data['kd_prosedur_sekunder2'] ?? '',
            'prosedur_sekunder3'     => $data['prosedur_sekunder3'] ?? '',
            'kd_prosedur_sekunder3'  => $data['kd_prosedur_sekunder3'] ?? '',
            'alergi'                 => $data['alergi'] ?? '',
            'diet'                   => $data['diet'] ?? '',
            'lab_belum'              => $data['lab_belum'] ?? '',
            'edukasi'                => $data['edukasi'] ?? '',
            'cara_keluar'            => $caraKeluar,
            'ket_keluar'             => $ketKeluar,
            'keadaan'                => $keadaan,
            'ket_keadaan'            => $ketKeadaan,
            'dilanjutkan'            => $dilanjutkan,
            'ket_dilanjutkan'        => $ketDilanjutkan,
            'kontrol'                => $kontrol,
            'obat_pulang'            => $data['obat_pulang'] ?? '',
        ];

        ResumePasienRanap::updateOrCreate(
            ['no_rawat' => $noRawat],
            $payload
        );

        return [
            'status'  => 'success',
            'message' => 'Resume medis rawat inap berhasil disimpan.',
        ];
    }
}
