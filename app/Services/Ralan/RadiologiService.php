<?php

namespace App\Services\Ralan;

use App\Models\PermintaanRadiologi;
use App\Models\PermintaanPemeriksaanRadiologi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RadiologiService
{
    /** @return array<int, array{id:string, text:string}> */
    public function cariPemeriksaan(string $search, ?string $noRawat): array
    {
        $kdPj = '-';
        if ($noRawat) {
            $reg = DB::table('reg_periksa')->where('no_rawat', $noRawat)->first();
            if ($reg) {
                $kdPj = $reg->kd_pj;
            }
        }

        $rows = DB::table('jns_perawatan_radiologi')
            ->where('status', '1')
            ->where(function ($q) use ($kdPj) {
                $q->where('kd_pj', $kdPj)->orWhere('kd_pj', '-');
            })
            ->where(function ($q) use ($search) {
                $q->where('kd_jenis_prw', 'like', "%$search%")
                  ->orWhere('nm_perawatan', 'like', "%$search%");
            })
            ->orderByRaw("FIELD(kd_pj, ?, '-')", [$kdPj])
            ->limit(20)
            ->get();

        return $rows->map(fn ($p) => [
            'id'   => $p->kd_jenis_prw,
            'text' => $p->kd_jenis_prw . ' - ' . $p->nm_perawatan,
        ])->all();
    }

    public function simpanPermintaan(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tgl = Carbon::now()->format('Y-m-d');
            $jam = Carbon::now()->format('H:i:s');
            $noOrder = 'PR' . str_replace('-', '', $tgl) . $this->nextNumber($tgl);

            $reg = DB::table('reg_periksa')->where('no_rawat', $data['no_rawat'])->first();
            $statusLanjut = ($reg && strtolower($reg->status_lanjut) === 'ranap') ? 'ranap' : 'ralan';

            PermintaanRadiologi::create([
                'noorder'            => $noOrder,
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

            foreach ($data['kd_jenis_prw_rad'] as $kdJenis) {
                PermintaanPemeriksaanRadiologi::create([
                    'noorder'      => $noOrder,
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

    private function nextNumber(string $date): string
    {
        $lastNo = DB::table('permintaan_radiologi')
            ->where('tgl_permintaan', $date)
            ->max(DB::raw('CONVERT(RIGHT(noorder, 4), signed)')) ?? 0;

        return sprintf('%04s', ($lastNo + 1));
    }
}
