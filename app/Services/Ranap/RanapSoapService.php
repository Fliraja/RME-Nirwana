<?php

namespace App\Services\Ranap;

use App\Models\PemeriksaanRanap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RanapSoapService
{
    /**
     * Ambil riwayat SOAP untuk rawat inap tertentu
     */
    public function getRiwayatSoap(string $noRawat)
    {
        return DB::table('pemeriksaan_ranap')
            ->leftJoin('pegawai', 'pemeriksaan_ranap.nip', '=', 'pegawai.nik')
            ->where('pemeriksaan_ranap.no_rawat', $noRawat)
            ->where(function ($q) {
                $q->whereNotNull('pemeriksaan_ranap.keluhan')
                  ->orWhereNotNull('pemeriksaan_ranap.pemeriksaan')
                  ->orWhereNotNull('pemeriksaan_ranap.penilaian');
            })
            ->select([
                'pemeriksaan_ranap.no_rawat',
                'pemeriksaan_ranap.tgl_perawatan',
                'pemeriksaan_ranap.jam_rawat',
                'pemeriksaan_ranap.keluhan',
                'pemeriksaan_ranap.pemeriksaan',
                'pemeriksaan_ranap.penilaian',
                'pemeriksaan_ranap.rtl',
                'pemeriksaan_ranap.instruksi',
                'pemeriksaan_ranap.evaluasi',
                'pemeriksaan_ranap.nip',
                'pegawai.nama as nama_petugas',
            ])
            ->orderBy('pemeriksaan_ranap.tgl_perawatan', 'desc')
            ->orderBy('pemeriksaan_ranap.jam_rawat', 'desc')
            ->get();
    }

    /**
     * Simpan SOAP (mendukung insert baru atau update entri jam tertentu)
     */
    public function simpan(array $data, string $nip): array
    {
        $noRawat = $data['no_rawat'];
        $isEdit = !empty($data['mode_edit_soap']) && $data['mode_edit_soap'] == '1';

        $keluhan = $data['keluhan'] ?? '';
        $pemeriksaan = $data['pemeriksaan'] ?? '';
        $penilaian = $data['penilaian'] ?? '';
        $rtl = $data['rtl'] ?? '';
        $instruksi = $data['instruksi'] ?? '';
        $evaluasi = $data['evaluasi'] ?? '-';

        if ($isEdit) {
            $tglEdit = $data['tgl_perawatan_edit_soap'];
            $jamEdit = $data['jam_rawat_edit_soap'];

            $affected = DB::table('pemeriksaan_ranap')
                ->where('no_rawat', $noRawat)
                ->where('tgl_perawatan', $tglEdit)
                ->where('jam_rawat', $jamEdit)
                ->update([
                    'keluhan'     => $keluhan,
                    'pemeriksaan' => $pemeriksaan,
                    'penilaian'   => $penilaian,
                    'rtl'         => $rtl,
                    'instruksi'   => $instruksi,
                    'evaluasi'    => $evaluasi,
                    'nip'         => $nip,
                ]);

            return [
                'status'  => 'success',
                'message' => 'Catatan SOAP berhasil diperbarui',
            ];
        } else {
            $tglPerawatan = !empty($data['tgl_perawatan']) ? $data['tgl_perawatan'] : Carbon::now()->format('Y-m-d');
            $jamRawat = !empty($data['jam_rawat']) ? $data['jam_rawat'] : Carbon::now()->format('H:i:s');

            DB::table('pemeriksaan_ranap')->insert([
                'no_rawat'      => $noRawat,
                'tgl_perawatan' => $tglPerawatan,
                'jam_rawat'     => $jamRawat,
                'suhu_tubuh'    => '',
                'tensi'         => '-',
                'nadi'          => '',
                'respirasi'     => '',
                'tinggi'        => '',
                'berat'         => '',
                'spo2'          => '',
                'gcs'           => '',
                'kesadaran'     => 'Compos Mentis',
                'keluhan'       => $keluhan,
                'pemeriksaan'   => $pemeriksaan,
                'alergi'        => '',
                'penilaian'     => $penilaian,
                'rtl'           => $rtl,
                'instruksi'     => $instruksi,
                'evaluasi'      => $evaluasi,
                'nip'           => $nip,
            ]);

            return [
                'status'  => 'success',
                'message' => 'Catatan SOAP baru berhasil disimpan',
            ];
        }
    }

    /**
     * Hapus SOAP
     */
    public function hapus(string $noRawat, string $tgl, string $jam, string $nip, bool $isAdmin = false): array
    {
        $query = DB::table('pemeriksaan_ranap')
            ->where('no_rawat', $noRawat)
            ->where('tgl_perawatan', $tgl)
            ->where('jam_rawat', $jam);

        if (!$isAdmin) {
            $query->where('nip', $nip);
        }

        $deleted = $query->delete();

        if ($deleted) {
            return ['status' => 'success', 'message' => 'Data SOAP berhasil dihapus'];
        }

        return ['status' => 'error', 'message' => 'Gagal menghapus data atau Anda tidak memiliki akses'];
    }
}
