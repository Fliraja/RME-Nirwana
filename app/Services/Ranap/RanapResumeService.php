<?php

namespace App\Services\Ranap;

use App\Models\DiagnosaPasien;
use App\Models\Dokter;
use App\Models\ProsedurPasien;
use App\Models\ResumePasienRanap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RanapResumeService
{
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

        $alergiPasien = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->whereNotNull('alergi')
            ->where('alergi', '!=', '')
            ->where('alergi', '!=', '-')
            ->value('alergi') ?? '';

        $kamar = DB::table('kamar_inap')
            ->where('no_rawat', $noRawat)
            ->orderBy('tgl_masuk', 'asc')
            ->first();

        $dpjp = DB::table('dpjp_ranap')
            ->join('dokter', 'dpjp_ranap.kd_dokter', '=', 'dokter.kd_dokter')
            ->where('dpjp_ranap.no_rawat', $noRawat)
            ->select('dokter.kd_dokter', 'dokter.nm_dokter')
            ->first();

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

        // Pemeriksaan fisik / keluhan terakhir dari SOAP ranap
        $soapTerakhir = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->orderBy('tgl_perawatan', 'desc')
            ->orderBy('jam_rawat', 'desc')
            ->first();

        // SOAP pertama saat MRS
        $soapAwal = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
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
            'obatRsList'
        );
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
