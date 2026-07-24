<?php

namespace App\Services\Ralan;

use App\Models\PermintaanLab;
use App\Models\PermintaanPemeriksaanLab;
use App\Models\PermintaanDetailPermintaanLab;
use App\Models\TemplateLaboratorium;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaboratoriumService
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

        $rows = DB::table('jns_perawatan_lab')
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
            $noOrder = 'PL' . str_replace('-', '', $tgl) . $this->nextNumber($tgl);

            $reg = DB::table('reg_periksa')->where('no_rawat', $data['no_rawat'])->first();
            $statusLanjut = ($reg && strtolower($reg->status_lanjut) === 'ranap') ? 'ranap' : 'ralan';

            PermintaanLab::create([
                'noorder'            => $noOrder,
                'no_rawat'           => $data['no_rawat'],
                'tgl_permintaan'     => $tgl,
                'jam_permintaan'     => $jam,
                'tgl_sampel'         => null,
                'jam_sampel'         => null,
                'tgl_hasil'          => null,
                'jam_hasil'          => null,
                'dokter_perujuk'     => Auth::user()->decrypted_id,
                'status'             => $statusLanjut,
                'informasi_tambahan' => $data['informasi_tambahan'] ?? '-',
                'diagnosa_klinis'    => $data['diagnosa_klinis'] ?? '-',
            ]);

            foreach ($data['kd_jenis_prw'] as $kdJenis) {
                PermintaanPemeriksaanLab::create([
                    'noorder'      => $noOrder,
                    'kd_jenis_prw' => $kdJenis,
                    'stts_bayar'   => 'Belum',
                ]);

                foreach (($data['detail_lab'][$kdJenis] ?? []) as $idTemplate) {
                    PermintaanDetailPermintaanLab::create([
                        'noorder'      => $noOrder,
                        'kd_jenis_prw' => $kdJenis,
                        'id_template'  => $idTemplate,
                        'stts_bayar'   => 'Belum',
                    ]);
                }
            }

            return [
                'status'  => 'success-lab',
                'message' => 'Permintaan Lab berhasil dikirim dengan nomor: ' . $noOrder,
                'noorder' => $noOrder,
            ];
        });
    }

    public function templates(string $kdJenisPrw)
    {
        return TemplateLaboratorium::where('kd_jenis_prw', $kdJenisPrw)
            ->select('id_template', 'Pemeriksaan')
            ->get();
    }

    public function hapus(?string $noorder, ?string $kdJenis, ?string $idTemplate): array
    {
        return DB::transaction(function () use ($noorder, $kdJenis, $idTemplate) {
            $isProcessed = DB::table('permintaan_pemeriksaan_lab')
                ->where('noorder', $noorder)
                ->where('stts_bayar', 'Sudah')
                ->exists();

            if ($isProcessed) {
                return ['status' => 'error', 'message' => 'Data sudah diproses lab!', 'code' => 403];
            }

            if ($noorder && $kdJenis && $idTemplate) {
                DB::table('permintaan_detail_permintaan_lab')
                    ->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenis, 'id_template' => $idTemplate])
                    ->delete();
                $msg = 'Item detail berhasil dihapus.';
            } elseif ($noorder && $kdJenis) {
                DB::table('permintaan_detail_permintaan_lab')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenis])->delete();
                DB::table('permintaan_pemeriksaan_lab')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenis])->delete();
                $msg = 'Jenis pemeriksaan berhasil dihapus.';
            } else {
                DB::table('permintaan_detail_permintaan_lab')->where('noorder', $noorder)->delete();
                DB::table('permintaan_pemeriksaan_lab')->where('noorder', $noorder)->delete();
                DB::table('permintaan_lab')->where('noorder', $noorder)->delete();
                $msg = 'Seluruh order berhasil dibatalkan.';
            }

            if (DB::table('permintaan_pemeriksaan_lab')->where('noorder', $noorder)->count() === 0) {
                DB::table('permintaan_lab')->where('noorder', $noorder)->delete();
            }

            return ['status' => 'success', 'message' => $msg];
        });
    }

    private function nextNumber(string $date): string
    {
        $lastNo = DB::table('permintaan_lab')
            ->where('tgl_permintaan', $date)
            ->max(DB::raw('CONVERT(RIGHT(noorder, 4), signed)')) ?? 0;

        return sprintf('%04s', ($lastNo + 1));
    }
}
