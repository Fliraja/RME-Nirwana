<?php

namespace App\Services\Ralan;

use App\Models\PermintaanRadiologi;
use App\Models\PermintaanPemeriksaanRadiologi;
use App\Support\NomorUrut;
use App\Support\PenjaminResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RadiologiService
{
    /** @return array<int, array{id:string, text:string}> */
    public function cariPemeriksaan(string $search, ?string $noRawat): array
    {
        $kdPj = null;
        $statusLanjut = null;
        if ($noRawat) {
            $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();
            if ($reg) {
                $kdPj = $reg->kd_pj;
                $statusLanjut = $reg->status_lanjut;
            }
        }

        $target = PenjaminResolver::radiologiPrefix($statusLanjut, $kdPj);

        // Ketat: hanya kode dengan prefix RAD sesuai penjamin (RAD.RJ/RI/B/P).
        $rows = DB::table('jns_perawatan_radiologi')
            ->where('status', '1')
            ->where('kd_jenis_prw', 'like', $target . '%')
            ->where(function ($q) use ($search) {
                $q->where('kd_jenis_prw', 'like', "%$search%")
                  ->orWhere('nm_perawatan', 'like', "%$search%");
            })
            ->orderBy('kd_jenis_prw')
            ->limit(30)
            ->get();

        return $rows->map(fn ($p) => [
            'id'   => $p->kd_jenis_prw,
            'text' => $p->kd_jenis_prw . ' - ' . $p->nm_perawatan,
        ])->all();
    }

    public function simpanPermintaan(array $data): array
    {
        return NomorUrut::denganRetry(function () use ($data) {
            $now = Carbon::now();
            $tgl = $now->format('Y-m-d');
            $jam = $now->format('H:i:s');

            // Nomor final di DB: PR + ymd + 2 digit (ACSN software radiologi maks 10 karakter).
            $urut = NomorUrut::berikutnya('permintaan_radiologi', 'noorder', 'PR' . $now->format('ymd'), 2);
            if ($urut > 99) {
                return [
                    'status'  => 'error',
                    'message' => 'Nomor order radiologi hari ini sudah mencapai batas 99.',
                    'code'    => 422,
                ];
            }
            $noOrder = 'PR' . $now->format('ymd') . sprintf('%02d', $urut);
            // Trigger trg_format_noorder_* memotong PR + Ymd + 4 digit menjadi $noOrder.
            $noOrderInput = 'PR' . $now->format('Ymd') . sprintf('%04d', $urut);

            $reg = DB::table('reg_periksa')->where('no_rawat', $data['no_rawat'])->first();
            $statusLanjut = ($reg && strtolower($reg->status_lanjut) === 'ranap') ? 'ranap' : 'ralan';

            PermintaanRadiologi::create([
                'noorder'            => $noOrderInput,
                'no_rawat'           => $data['no_rawat'],
                'tgl_permintaan'     => $tgl,
                'jam_permintaan'     => $jam,
                'tgl_sampel'         => '0000-00-00',
                'jam_sampel'         => '00:00:00',
                'tgl_hasil'          => '0000-00-00',
                'jam_hasil'          => '00:00:00',
                'dokter_perujuk'     => Auth::user()->decrypted_id,
                'status'             => $statusLanjut,
                'informasi_tambahan' => $data['informasi_tambahan'] ?? '-',
                'diagnosa_klinis'    => $data['diagnosa_klinis'] ?? '-',
            ]);

            $kdJenisList = $data['kd_jenis_prw_rad'] ?? $data['kd_jenis_prw'] ?? [];
            foreach ($kdJenisList as $kdJenis) {
                PermintaanPemeriksaanRadiologi::create([
                    'noorder'      => $noOrderInput,
                    'kd_jenis_prw' => $kdJenis,
                    'stts_bayar'   => 'Belum',
                ]);
            }

            return [
                'status'  => 'success-rad',
                'message' => 'Permintaan Radiologi berhasil dikirim dengan nomor: ' . $noOrder,
                'noorder' => $noOrder,
            ];
        });
    }

    public function hapus(string $noorder, ?string $kdJenisPrw): array
    {
        return DB::transaction(function () use ($noorder, $kdJenisPrw) {
            $isProcessed = DB::table('permintaan_pemeriksaan_radiologi')
                ->where('noorder', $noorder)
                ->where('stts_bayar', 'Sudah')
                ->exists();

            if ($isProcessed) {
                return ['status' => 'error', 'message' => 'Gagal! Data sudah diproses.', 'code' => 403];
            }

            if ($kdJenisPrw) {
                DB::table('permintaan_pemeriksaan_radiologi')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw])->delete();
            } else {
                DB::table('permintaan_pemeriksaan_radiologi')->where('noorder', $noorder)->delete();
                DB::table('permintaan_radiologi')->where('noorder', $noorder)->delete();
            }

            if (DB::table('permintaan_pemeriksaan_radiologi')->where('noorder', $noorder)->count() === 0) {
                DB::table('permintaan_radiologi')->where('noorder', $noorder)->delete();
            }

            return ['status' => 'success-hapus-rad', 'message' => 'Data berhasil dihapus'];
        });
    }
}
