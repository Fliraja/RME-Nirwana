<?php

namespace App\Services\Ralan;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AntrolBpjsService
{
    /**
     * Insert jam periksa perawat (hanya 1x, jika masih 00:00:00)
     * Ini dipanggil saat nurse/perawat akses data pasien
     * 
     * PENTING: Multiple tabs/windows - service handle concurrent access
     * via "insertJamPeriksaPerawatOnce" logic (hanya update jika jam masih kosong)
     */
    public function insertJamPeriksaPerawatOnce($no_rawat, $jam)
    {
        try {
            $no_rawat = trim($no_rawat);
            
            // 1. Cek kd_pj = BPJ (hanya untuk pasien BPJS)
            $cek_pj = DB::table('reg_periksa')
                ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('penjab.kd_pj')
                ->first();

            if (!$cek_pj || trim($cek_pj->kd_pj) !== 'BPJ') {
                Log::info("AntrolBpjs: Bukan pasien BPJS", ['no_rawat' => $no_rawat]);
                return ['success' => false, 'message' => 'Bukan pasien BPJS'];
            }

            // 2. Cek apakah data sudah ada
            $antrol = DB::table('antrol_bpjs')
                ->where('no_rawat', $no_rawat)
                ->select('jam_periksa_perawat')
                ->first();

            if ($antrol) {
                // 3. Update hanya jika jam masih 00:00:00 atau NULL
                if ($antrol->jam_periksa_perawat === '00:00:00' || is_null($antrol->jam_periksa_perawat)) {
                    DB::table('antrol_bpjs')
                        ->where('no_rawat', $no_rawat)
                        ->update(['jam_periksa_perawat' => $jam]);

                    Log::info("AntrolBpjs: Jam perawat set (first time)", [
                        'no_rawat' => $no_rawat,
                        'jam' => $jam
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Jam perawat berhasil disimpan'
                    ];
                } else {
                    Log::info("AntrolBpjs: Jam perawat sudah ada (skip update)", [
                        'no_rawat' => $no_rawat,
                        'existing_jam' => $antrol->jam_periksa_perawat
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Jam perawat sudah tersimpan sebelumnya'
                    ];
                }
            }

            return ['success' => false, 'message' => 'Data antrol tidak ditemukan'];

        } catch (Exception $e) {
            Log::error("AntrolBpjs insertJamPeriksaPerawatOnce Error", [
                'no_rawat' => $no_rawat,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Upsert jam periksa dokter dengan logika dinamis
     * Dipanggil saat dokter submit pemeriksaan SOAP atau Vital Sign
     * 
     * Logika:
     * - Jika dokter submit < 1 menit dari jam perawat → paksa ke jam_perawat + 1 menit
     * - Jika dokter submit ≥ 1 menit → pakai jam realtime saat klik
     * 
     * PENTING: Handle multiple tabs - setiap tab punya timestamp sendiri
     */
    public function upsertAntrolBpjsJam($no_rawat, $field, $jam_submit)
    {
        try {
            $no_rawat = trim($no_rawat);

            // Validasi field hanya jam_periksa_dokter
            if ($field !== 'jam_periksa_dokter') {
                return ['success' => false, 'message' => 'Field tidak valid'];
            }

            // 1. Cek kd_pj = BPJ
            $cek_pj = DB::table('reg_periksa')
                ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('penjab.kd_pj')
                ->first();

            if (!$cek_pj || trim($cek_pj->kd_pj) !== 'BPJ') {
                return ['success' => false, 'message' => 'Bukan pasien BPJS'];
            }

            // 2. Cek jam perawat untuk logika dinamis
            $antrol = DB::table('antrol_bpjs')
                ->where('no_rawat', $no_rawat)
                ->select('jam_periksa_perawat')
                ->first();

            if (!$antrol) {
                return ['success' => false, 'message' => 'Data antrol tidak ditemukan'];
            }

            $jam_perawat = $antrol->jam_periksa_perawat;
            $jam_dokter_final = $jam_submit; // Default: pakai jam submit realtime

            // 3. Logika dinamis - jika jam_perawat valid
            if ($jam_perawat && $jam_perawat !== '00:00:00') {
                $unix_perawat = strtotime($jam_perawat);
                $unix_submit = strtotime($jam_submit);
                $batas_minimal = $unix_perawat + 61; // Jam perawat + 1 menit 1 detik

                if ($unix_submit < $batas_minimal) {
                    // Dokter submit CEPAT (< 1 menit) → paksa ke batas minimal
                    $jam_dokter_final = date('H:i:s', $batas_minimal);
                    Log::info("AntrolBpjs: Dokter submit CEPAT, paksa ke batas minimal", [
                        'no_rawat' => $no_rawat,
                        'jam_perawat' => $jam_perawat,
                        'jam_submit' => $jam_submit,
                        'jam_final' => $jam_dokter_final,
                        'selisih_detik' => $unix_submit - $unix_perawat
                    ]);
                } else {
                    // Dokter submit LAMA (≥ 1 menit) → pakai realtime
                    Log::info("AntrolBpjs: Dokter submit LAMA, pakai realtime", [
                        'no_rawat' => $no_rawat,
                        'jam_perawat' => $jam_perawat,
                        'jam_submit' => $jam_submit,
                        'selisih_detik' => $unix_submit - $unix_perawat
                    ]);
                }
            }

            // 4. Update database
            DB::table('antrol_bpjs')
                ->where('no_rawat', $no_rawat)
                ->update([$field => $jam_dokter_final]);

            Log::info("AntrolBpjs: Jam dokter berhasil diupdate", [
                'no_rawat' => $no_rawat,
                'jam_dokter' => $jam_dokter_final
            ]);

            return [
                'success' => true,
                'jam' => $jam_dokter_final,
                'message' => 'Jam dokter berhasil diperbarui'
            ];

        } catch (Exception $e) {
            Log::error("AntrolBpjs upsertAntrolBpjsJam Error", [
                'no_rawat' => $no_rawat,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Cek apakah pasien sudah punya entry antrol_bpjs
     */
    public function getAntrolData($no_rawat)
    {
        return DB::table('antrol_bpjs')
            ->where('no_rawat', trim($no_rawat))
            ->first();
    }

    /**
     * Get task history untuk audit trail
     */
    public function getTaskHistory($no_rawat)
    {
        return DB::table('antrol_bpjs')
            ->where('no_rawat', trim($no_rawat))
            ->select('jam_periksa_perawat', 'jam_periksa_dokter')
            ->get();
    }
}
