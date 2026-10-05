<?php

namespace App\Services\Ranap;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RanapVitalSignService
{
    /**
     * Ambil riwayat TTV beserta SBAR untuk rawat inap
     */
    public function getRiwayatTtv(string $noRawat)
    {
        return DB::table('pemeriksaan_ranap')
            ->leftJoin('catatan_sbar_ranap', function ($join) {
                $join->on('pemeriksaan_ranap.no_rawat', '=', 'catatan_sbar_ranap.no_rawat')
                     ->on('pemeriksaan_ranap.tgl_perawatan', '=', 'catatan_sbar_ranap.tgl_perawatan')
                     ->on('pemeriksaan_ranap.jam_rawat', '=', 'catatan_sbar_ranap.jam_rawat');
            })
            ->leftJoin('pegawai', 'pemeriksaan_ranap.nip', '=', 'pegawai.nik')
            ->where('pemeriksaan_ranap.no_rawat', $noRawat)
            ->where(function ($q) {
                $q->where(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.suhu_tubuh')
                      ->where('pemeriksaan_ranap.suhu_tubuh', '!=', '')
                      ->where('pemeriksaan_ranap.suhu_tubuh', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.tensi')
                      ->where('pemeriksaan_ranap.tensi', '!=', '')
                      ->where('pemeriksaan_ranap.tensi', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.nadi')
                      ->where('pemeriksaan_ranap.nadi', '!=', '')
                      ->where('pemeriksaan_ranap.nadi', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.respirasi')
                      ->where('pemeriksaan_ranap.respirasi', '!=', '')
                      ->where('pemeriksaan_ranap.respirasi', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.spo2')
                      ->where('pemeriksaan_ranap.spo2', '!=', '')
                      ->where('pemeriksaan_ranap.spo2', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.gcs')
                      ->where('pemeriksaan_ranap.gcs', '!=', '')
                      ->where('pemeriksaan_ranap.gcs', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.tinggi')
                      ->where('pemeriksaan_ranap.tinggi', '!=', '')
                      ->where('pemeriksaan_ranap.tinggi', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('pemeriksaan_ranap.berat')
                      ->where('pemeriksaan_ranap.berat', '!=', '')
                      ->where('pemeriksaan_ranap.berat', '!=', '-');
                })
                ->orWhere(function ($v) {
                    $v->whereNotNull('catatan_sbar_ranap.sbar')
                      ->where('catatan_sbar_ranap.sbar', '!=', '')
                      ->where('catatan_sbar_ranap.sbar', '!=', '-');
                });
            })
            ->select([
                'pemeriksaan_ranap.no_rawat',
                'pemeriksaan_ranap.tgl_perawatan',
                'pemeriksaan_ranap.jam_rawat',
                'pemeriksaan_ranap.suhu_tubuh',
                'pemeriksaan_ranap.tensi',
                'pemeriksaan_ranap.nadi',
                'pemeriksaan_ranap.respirasi',
                'pemeriksaan_ranap.tinggi',
                'pemeriksaan_ranap.berat',
                'pemeriksaan_ranap.spo2',
                'pemeriksaan_ranap.gcs',
                'pemeriksaan_ranap.kesadaran',
                'pemeriksaan_ranap.alergi',
                'pemeriksaan_ranap.nip',
                'catatan_sbar_ranap.sbar',
                'pegawai.nama as nama_petugas',
            ])
            ->orderBy('pemeriksaan_ranap.tgl_perawatan', 'desc')
            ->orderBy('pemeriksaan_ranap.jam_rawat', 'desc')
            ->get();
    }

    /**
     * Simpan data TTV dan SBAR (insert baru atau edit spesifik)
     */
    public function simpan(array $data, string $nip): array
    {
        return DB::transaction(function () use ($data, $nip) {
            $noRawat = $data['no_rawat'];
            $isEdit = !empty($data['mode_edit_ttv']) && $data['mode_edit_ttv'] == '1';

            $suhu = substr($data['suhu_tubuh'] ?? '', 0, 5);
            $tensi = substr($data['tensi'] ?? '-', 0, 7);
            $nadi = substr($data['nadi'] ?? '', 0, 3);
            $respirasi = substr($data['respirasi'] ?? '', 0, 3);
            $tinggi = substr($data['tinggi'] ?? '', 0, 5);
            $berat = substr($data['berat'] ?? '', 0, 5);
            $spo2 = substr($data['spo2'] ?? '', 0, 3);
            $gcs = substr($data['gcs'] ?? '', 0, 10);
            $kesadaran = !empty($data['kesadaran']) ? $data['kesadaran'] : 'Compos Mentis';
            $alergi = substr($data['alergi'] ?? '', 0, 400);
            $sbar = trim($data['sbar'] ?? '');

            if ($isEdit) {
                $tglEdit = $data['tgl_perawatan_edit_ttv'];
                $jamEdit = $data['jam_rawat_edit_ttv'];

                DB::table('pemeriksaan_ranap')
                    ->where('no_rawat', $noRawat)
                    ->where('tgl_perawatan', $tglEdit)
                    ->where('jam_rawat', $jamEdit)
                    ->update([
                        'suhu_tubuh' => $suhu,
                        'tensi'      => $tensi,
                        'nadi'       => $nadi,
                        'respirasi'  => $respirasi,
                        'tinggi'     => $tinggi,
                        'berat'      => $berat,
                        'spo2'       => $spo2,
                        'gcs'        => $gcs,
                        'kesadaran'  => $kesadaran,
                        'alergi'     => $alergi,
                        'nip'        => $nip,
                    ]);

                if ($sbar !== '') {
                    $existing = DB::table('catatan_sbar_ranap')
                        ->where('no_rawat', $noRawat)
                        ->where('tgl_perawatan', $tglEdit)
                        ->where('jam_rawat', $jamEdit)
                        ->first();

                    if ($existing) {
                        DB::table('catatan_sbar_ranap')
                            ->where('no_rawat', $noRawat)
                            ->where('tgl_perawatan', $tglEdit)
                            ->where('jam_rawat', $jamEdit)
                            ->update(['sbar' => $sbar, 'nip' => $nip]);
                    } else {
                        DB::table('catatan_sbar_ranap')->insert([
                            'no_rawat'      => $noRawat,
                            'tgl_perawatan' => $tglEdit,
                            'jam_rawat'     => $jamEdit,
                            'sbar'          => $sbar,
                            'nip'           => $nip,
                        ]);
                    }
                } else {
                    DB::table('catatan_sbar_ranap')
                        ->where('no_rawat', $noRawat)
                        ->where('tgl_perawatan', $tglEdit)
                        ->where('jam_rawat', $jamEdit)
                        ->delete();
                }

                return ['status' => 'success', 'message' => 'Data TTV & SBAR berhasil diperbarui'];
            } else {
                $tglPerawatan = !empty($data['tgl_perawatan']) ? $data['tgl_perawatan'] : Carbon::now()->format('Y-m-d');
                $jamRawat = !empty($data['jam_rawat']) ? $data['jam_rawat'] : Carbon::now()->format('H:i:s');

                DB::table('pemeriksaan_ranap')->insert([
                    'no_rawat'      => $noRawat,
                    'tgl_perawatan' => $tglPerawatan,
                    'jam_rawat'     => $jamRawat,
                    'suhu_tubuh'    => $suhu,
                    'tensi'         => $tensi,
                    'nadi'          => $nadi,
                    'respirasi'     => $respirasi,
                    'tinggi'        => $tinggi,
                    'berat'         => $berat,
                    'spo2'          => $spo2,
                    'gcs'           => $gcs,
                    'kesadaran'     => $kesadaran,
                    'keluhan'       => '',
                    'pemeriksaan'   => '',
                    'alergi'        => $alergi,
                    'penilaian'     => '',
                    'rtl'           => '',
                    'instruksi'     => '',
                    'evaluasi'      => '-',
                    'nip'           => $nip,
                ]);

                if ($sbar !== '') {
                    DB::table('catatan_sbar_ranap')->insert([
                        'no_rawat'      => $noRawat,
                        'tgl_perawatan' => $tglPerawatan,
                        'jam_rawat'     => $jamRawat,
                        'sbar'          => $sbar,
                        'nip'           => $nip,
                    ]);
                }

                return ['status' => 'success', 'message' => 'Data TTV & SBAR baru berhasil disimpan'];
            }
        });
    }

    /**
     * Hapus TTV dan SBAR
     */
    public function hapus(string $noRawat, string $tgl, string $jam, string $nip, bool $isAdmin = false): array
    {
        return DB::transaction(function () use ($noRawat, $tgl, $jam, $nip, $isAdmin) {
            $query = DB::table('pemeriksaan_ranap')
                ->where('no_rawat', $noRawat)
                ->where('tgl_perawatan', $tgl)
                ->where('jam_rawat', $jam);

            if (!$isAdmin) {
                $query->where('nip', $nip);
            }

            $deleted = $query->delete();
            if ($deleted) {
                DB::table('catatan_sbar_ranap')
                    ->where('no_rawat', $noRawat)
                    ->where('tgl_perawatan', $tgl)
                    ->where('jam_rawat', $jam)
                    ->delete();

                return ['status' => 'success', 'message' => 'Data TTV & SBAR berhasil dihapus'];
            }

            return ['status' => 'error', 'message' => 'Gagal menghapus data atau Anda tidak memiliki akses'];
        });
    }
}
