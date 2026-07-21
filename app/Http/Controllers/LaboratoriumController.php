<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PermintaanLab;
use Illuminate\Support\Facades\DB;
use App\Models\TemplateLaboratorium;
use App\Models\PermintaanPemeriksaanLab;
use App\Models\PermintaanDetailPermintaanLab;
use Illuminate\Support\Facades\Auth;

class LaboratoriumController extends Controller
{
    public function getLabPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        
        $pasien = DB::table('reg_periksa')
                    ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                    ->where('no_rawat', $no_rawat)
                    ->first();

        $riwayat = PermintaanLab::with(['pemeriksaan.jenisPerawatan'])
                    ->where('no_rawat', $no_rawat)
                    ->where('tgl_permintaan', date('Y-m-d'))
                    ->get();

        return view('ralan.lab', compact('pasien', 'riwayat'));
    }

    public function getPemeriksaan(Request $request)
    {
        $search = $request->search;
        $no_rawat = $request->no_rawat;

        $kdPj = '-';
        if ($no_rawat) {
            $regPeriksa = DB::table('reg_periksa')->where('no_rawat', $no_rawat)->first();
            if ($regPeriksa) {
                $kdPj = $regPeriksa->kd_pj;
            }
        }

        $pemeriksaan = DB::table('jns_perawatan_lab')
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

        $response = [];
        foreach ($pemeriksaan as $p) {
            $response[] = [
                'id'   => $p->kd_jenis_prw,
                'text' => $p->kd_jenis_prw . ' - ' . $p->nm_perawatan
            ];
        }

        return response()->json($response);
    }

    public function storePermintaanLab(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
            'kd_jenis_prw' => 'required|array|min:1', 
        ]);

        return DB::transaction(function () use ($request) {
            $tgl_sekarang = Carbon::now()->format('Y-m-d');
            $jam_sekarang = Carbon::now()->format('H:i:s');

            $get_next_number = $this->generateNoOrder($tgl_sekarang);
            $no_order = 'PL' . str_replace('-', '', $tgl_sekarang) . $get_next_number;

            $kd_dokter = Auth::user()->decrypted_id;

            $regPeriksa = DB::table('reg_periksa')->where('no_rawat', $request->no_rawat)->first();
            $statusLanjut = ($regPeriksa && strtolower($regPeriksa->status_lanjut) === 'ranap') ? 'ranap' : 'ralan';

            $permintaan = PermintaanLab::create([
                'noorder'           => $no_order,
                'no_rawat'         => $request->no_rawat,
                'tgl_permintaan'   => $tgl_sekarang,
                'jam_permintaan'   => $jam_sekarang,
                'tgl_sampel'   => null,
                'jam_sampel'   => null,
                'tgl_hasil'   => null,
                'jam_hasil'   => null,
                'dokter_perujuk'   => $kd_dokter,
                'status'           => $statusLanjut,
                'informasi_tambahan' => $request->informasi_tambahan ?? '-',
                'diagnosa_klinis'  => $request->diagnosa_klinis ?? '-',
            ]);

            foreach ($request->kd_jenis_prw as $kd_jenis) {
                
                PermintaanPemeriksaanLab::create([
                    'noorder'      => $no_order,
                    'kd_jenis_prw' => $kd_jenis,
                    'stts_bayar'   => 'Belum'
                ]);

                if ($request->has("detail_lab.{$kd_jenis}")) {
                    foreach ($request->detail_lab[$kd_jenis] as $id_template) {
                        PermintaanDetailPermintaanLab::create([
                            'noorder'      => $no_order,
                            'kd_jenis_prw' => $kd_jenis,
                            'id_template'  => $id_template,
                            'stts_bayar'   => 'Belum' 
                        ]);
                    }
                }
            }

            return response()->json([
                'status'  => 'success-lab',
                'message' => 'Permintaan Lab berhasil dikirim dengan nomor: ' . $no_order,
                'noorder' => $no_order
            ]);
        });
    }

    private function generateNoOrder($date)
    {
        $lastNo = DB::table('permintaan_lab')
            ->where('tgl_permintaan', $date)
            ->max(DB::raw('CONVERT(RIGHT(noorder, 4), signed)')) ?? 0;

        return sprintf('%04s', ($lastNo + 1));
    }

    public function getTemplates($kd_jenis_prw)
    {
        $templates = TemplateLaboratorium::where('kd_jenis_prw', $kd_jenis_prw)
            ->select('id_template', 'Pemeriksaan')
            ->get();

        return response()->json($templates);
    }

    public function destroyLab(Request $request)
    {
        $noorder     = $request->noorder;
        $kd_jenis    = $request->kd_jenis_prw; 
        $id_template = $request->id_template;  

        return DB::transaction(function () use ($noorder, $kd_jenis, $id_template) {
            $isProcessed = DB::table('permintaan_pemeriksaan_lab')
                ->where('noorder', $noorder)
                ->where('stts_bayar', 'Sudah')
                ->exists();

            if ($isProcessed) {
                return response()->json(['status' => 'error', 'message' => 'Data sudah diproses lab!'], 403);
            }

            if ($noorder && $kd_jenis && $id_template) {
                DB::table('permintaan_detail_permintaan_lab')
                    ->where(['noorder' => $noorder, 'kd_jenis_prw' => $kd_jenis, 'id_template' => $id_template])
                    ->delete();
                $msg = "Item detail berhasil dihapus.";
            }
            
            elseif ($noorder && $kd_jenis) {
                DB::table('permintaan_detail_permintaan_lab')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kd_jenis])->delete();
                DB::table('permintaan_pemeriksaan_lab')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kd_jenis])->delete();
                $msg = "Jenis pemeriksaan berhasil dihapus.";
            }

            else {
                DB::table('permintaan_detail_permintaan_lab')->where('noorder', $noorder)->delete();
                DB::table('permintaan_pemeriksaan_lab')->where('noorder', $noorder)->delete();
                DB::table('permintaan_lab')->where('noorder', $noorder)->delete();
                $msg = "Seluruh order berhasil dibatalkan.";
            }

            $sisaJasa = DB::table('permintaan_pemeriksaan_lab')->where('noorder', $noorder)->count();
            if ($sisaJasa == 0) {
                DB::table('permintaan_lab')->where('noorder', $noorder)->delete();
            }

            return response()->json(['status' => 'success', 'message' => $msg]);
        });
    }
}
