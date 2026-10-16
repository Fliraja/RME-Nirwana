<?php

namespace App\Services\Ranap;

use App\Exceptions\KonflikResumeException;
use App\Models\ResumePasienRanap;
use App\Models\RmeResumeRanap;
use App\Support\Alergi;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ResumeRanapService
{
    /** Sepuluh isian tab Resume dan kolom yang menentukan "terisi". */
    public const ISIAN = [
        'keluhan'     => ['keluhan_utama'],
        'perjalanan'  => ['jalannya_penyakit'],
        'pemeriksaan' => ['pemeriksaan_fisik'],
        'penunjang'   => ['hasil_laborat', 'pemeriksaan_penunjang'],
        'diagnosis'   => ['diagnosa_utama'],
        'tindakan'    => ['prosedur_utama'],
        'terapi'      => ['obat_di_rs'],
        'kondisi'     => ['keadaan'],
        'obat_pulang' => ['obat_pulang'],
        'instruksi'   => ['edukasi', 'kontrol'],
    ];

    public const PESAN_TIDAK_BERHAK = 'Hanya dokter IGD penerima dan DPJP pasien ini yang dapat mengubah resume.';

    private const TEKS = ['keluhan_utama', 'alasan', 'diagnosa_awal', 'jalannya_penyakit', 'pemeriksaan_fisik',
        'hasil_laborat', 'pemeriksaan_penunjang', 'obat_di_rs', 'obat_pulang', 'edukasi', 'diet', 'lab_belum'];
    private const DIAGNOSA = ['diagnosa_utama', 'diagnosa_sekunder', 'diagnosa_sekunder2', 'diagnosa_sekunder3', 'diagnosa_sekunder4'];
    private const PROSEDUR = ['prosedur_utama', 'prosedur_sekunder', 'prosedur_sekunder2', 'prosedur_sekunder3'];
    private const KONDISI = ['cara_keluar', 'ket_keluar', 'keadaan', 'ket_keadaan', 'dilanjutkan', 'ket_dilanjutkan'];

    public function bolehMenulis(string $noRawat, ?string $kdDokter): bool
    {
        if (!$kdDokter) {
            return false;
        }

        return DB::table('reg_periksa')->where('no_rawat', $noRawat)->where('kd_dokter', $kdDokter)->exists()
            || DB::table('dpjp_ranap')->where('no_rawat', $noRawat)->where('kd_dokter', $kdDokter)->exists();
    }

    public function dataTab(string $noRawat, ?string $kdDokter): array
    {
        $resume = ResumePasienRanap::find($noRawat);
        $meta = RmeResumeRanap::with(['pengubah', 'penanda'])->find($noRawat);

        return [
            'resume'       => $resume,
            'meta'         => $meta,
            'status'       => $meta?->status ?? ($resume ? 'draf' : 'belum'),
            'bolehMenulis' => $this->bolehMenulis($noRawat, $kdDokter),
            'terisi'       => $resume ? self::terisi($resume) : [],
            'kamar'        => DB::table('kamar_inap')->where('no_rawat', $noRawat)->orderBy('tgl_masuk')->orderBy('jam_masuk')->first(),
            'reg'          => DB::table('reg_periksa')->where('no_rawat', $noRawat)->first(),
        ];
    }

    /** @return list<string> kunci ISIAN yang sudah terisi */
    public static function terisi(object $resume): array
    {
        $isi = fn ($kolom) => !in_array(trim((string) ($resume->{$kolom} ?? '')), ['', '-'], true);

        return array_keys(array_filter(self::ISIAN, fn ($kolom) => (bool) array_filter($kolom, $isi)));
    }

    public function simpan(string $noRawat, array $data, string $kdDokter, bool $selesai): RmeResumeRanap
    {
        if (!$this->bolehMenulis($noRawat, $kdDokter)) {
            throw new AuthorizationException(self::PESAN_TIDAK_BERHAK);
        }

        return DB::transaction(function () use ($noRawat, $data, $kdDokter, $selesai) {
            $meta = RmeResumeRanap::with('pengubah')->lockForUpdate()->find($noRawat);
            if ($meta && array_key_exists('diubah_pada', $data)
                && (string) $data['diubah_pada'] !== $meta->diubah_pada->format('Y-m-d H:i:s')) {
                throw new KonflikResumeException($meta);
            }

            $lama = ResumePasienRanap::find($noRawat);
            ResumePasienRanap::updateOrCreate(['no_rawat' => $noRawat], $this->isi($noRawat, $data, $lama, $kdDokter));

            $sekarang = now()->startOfSecond();

            return RmeResumeRanap::updateOrCreate(['no_rawat' => $noRawat], [
                'status'       => $selesai ? 'selesai' : 'draf',
                'diubah_oleh'  => $kdDokter,
                'diubah_pada'  => $sekarang,
                'selesai_oleh' => $selesai ? $kdDokter : null,
                'selesai_pada' => $selesai ? $sekarang : null,
            ]);
        });
    }

    /** Isi baris resume_pasien_ranap. Field yang tidak dikirim mempertahankan nilai lama. */
    private function isi(string $noRawat, array $data, ?ResumePasienRanap $lama, string $kdDokter): array
    {
        $isi = ['kd_dokter' => $kdDokter];
        foreach (self::TEKS as $kolom) {
            $isi[$kolom] = array_key_exists($kolom, $data) ? trim((string) $data[$kolom]) : (string) ($lama->{$kolom} ?? '');
        }

        foreach (['diagnosa' => self::DIAGNOSA, 'prosedur' => self::PROSEDUR] as $field => $kolom) {
            if (array_key_exists($field, $data)) {
                $baris = array_values(array_filter(array_map(fn ($t) => trim((string) $t), (array) $data[$field]), 'strlen'));
                foreach ($kolom as $i => $k) {
                    $isi[$k] = $baris[$i] ?? '';
                }
            } else {
                foreach ($kolom as $k) {
                    $isi[$k] = (string) ($lama->{$k} ?? '');
                }
            }
        }
        // Pengodean ICD wewenang casemix (diagnosa_pasien/prosedur_pasien); dokter tidak mengode.
        foreach (array_merge(self::DIAGNOSA, self::PROSEDUR) as $k) {
            $isi['kd_' . $k] = '';
        }
        $isi['tindakan_dan_operasi'] = implode("\n", array_filter(array_map(fn ($k) => $isi[$k], self::PROSEDUR), 'strlen'));

        $isi = array_merge($isi, $this->kondisi($data, $lama));

        $kontrol = array_key_exists('kontrol', $data) ? $data['kontrol'] : $lama?->kontrol;
        $isi['kontrol'] = $kontrol && $isi['keadaan'] !== 'Meninggal' ? Carbon::parse($kontrol)->format('Y-m-d H:i:s') : null;

        $isi['alergi'] = mb_substr(Alergi::terakhirRanap($noRawat) ?? (string) ($lama->alergi ?? ''), 0, 100);

        return $isi;
    }

    /**
     * Kondisi pulang pilihan dokter ke kolom Khanza. Tanpa pilihan: nilai lama, atau kosong untuk resume baru
     * (tidak ada pilihan bawaan; sql_mode tidak strict menyimpan enum kosong).
     */
    private function kondisi(array $data, ?ResumePasienRanap $lama): array
    {
        $opsi = $data['kondisi_pulang_opsi'] ?? null;
        $rujukan = trim((string) ($data['nama_faskes_rujukan'] ?? ''));

        $peta = [
            'sembuh'              => ['Atas Izin Dokter', '', 'Sembuh', '', 'Kembali Ke RS', ''],
            'membaik'             => ['Atas Izin Dokter', '', 'Membaik', '', 'Kembali Ke RS', ''],
            'aps'                 => ['Pulang Atas Permintaan Sendiri', '', 'Membaik', '', 'Lainnya', 'APS'],
            'dirujuk'             => ['Pindah RS', $rujukan, 'Membaik', '', 'RS Lain', $rujukan],
            'meninggal_kurang_48' => ['Lainnya', 'Meninggal dunia < 48 jam', 'Meninggal', '< 48 jam', 'Lainnya', 'Meninggal dunia'],
            'meninggal_lebih_48'  => ['Lainnya', 'Meninggal dunia > 48 jam', 'Meninggal', '> 48 jam', 'Lainnya', 'Meninggal dunia'],
        ];

        if (isset($peta[$opsi])) {
            return array_combine(self::KONDISI, $peta[$opsi]);
        }

        return array_combine(self::KONDISI, array_map(fn ($k) => (string) ($lama->{$k} ?? ''), self::KONDISI));
    }
}
