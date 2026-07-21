<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PermintaanRadiologi;
use App\Models\PermintaanPemeriksaanRadiologi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RadiologiController extends Controller
{
    public function getRadiologiPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        
        $pasien = DB::table('reg_periksa')
                    ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                    ->where('no_rawat', $no_rawat)
                    ->first();

        $riwayat = PermintaanRadiologi::with(['pemeriksaan.jenisPerawatan'])
                    ->where('no_rawat', $no_rawat)
                    ->where('tgl_permintaan', date('Y-m-d'))
                    ->get();

        return view('ralan.radiologi', compact('pasien', 'riwayat'));
    }

    public function getPemeriksaanRadiologi(Request $request)
    {
        $search = $request->search;

        $pemeriksaan = DB::table('jns_perawatan_radiologi')
            ->where('status', '1') 
            ->where(function($q) use ($search) {
                $q->where('kd_jenis_prw', 'like', "%$search%")
                ->orWhere('nm_perawatan', 'like', "%$search%");
            })
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

    public function storePermintaanRadiologi(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
            'kd_jenis_prw_rad' => 'required|array|min:1', 
        ]);

        return DB::transaction(function () use ($request) {
            $tgl_sekarang = Carbon::now()->format('Y-m-d');
            $jam_sekarang = Carbon::now()->format('H:i:s');

            $get_next_number = $this->generateNoOrder($tgl_sekarang);
            $no_order = 'PR' . str_replace('-', '', $tgl_sekarang) . $get_next_number;

            $kd_dokter = Auth::user()->decrypted_id;

            $permintaan = PermintaanRadiologi::create([
                'noorder'            => $no_order,
                'no_rawat'           => $request->no_rawat,
                'tgl_permintaan'     => $tgl_sekarang,
                'jam_permintaan'     => $jam_sekarang,
                'tgl_sampel'         => null,
                'jam_sampel'         => null,
                'tgl_hasil'          => null,
                'jam_hasil'          => null,
                'dokter_perujuk'     => $kd_dokter,
                'status'             => 'ralan',
                'informasi_tambahan' => $request->informasi_tambahan ?? '-',
                'diagnosa_klinis'    => $request->diagnosa_klinis ?? '-',
            ]);

            foreach ($request->kd_jenis_prw_rad as $kd_jenis) {
                PermintaanPemeriksaanRadiologi::create([
                    'noorder'      => $no_order,
                    'kd_jenis_prw' => $kd_jenis,
                    'stts_bayar'   => 'Belum'
                ]);
            }

            return response()->json([
                'status'  => 'success-rad',
                'message' => 'Permintaan Radiologi berhasil dikirim dengan nomor: ' . $no_order,
                'noorder' => $no_order
            ]);
        });
    }

    private function generateNoOrder($date)
    {
        $lastNo = DB::table('permintaan_radiologi')
            ->where('tgl_permintaan', $date)
            ->max(DB::raw('CONVERT(RIGHT(noorder, 4), signed)')) ?? 0;

        return sprintf('%04s', ($lastNo + 1));
    }
    
    public function destroyRadiologi($noorder, $kd_jenis_prw = null)
    {
        return DB::transaction(function () use ($noorder, $kd_jenis_prw) {
            $isProcessed = DB::table('permintaan_pemeriksaan_radiologi')
                ->where('noorder', $noorder)
                ->where('stts_bayar', 'Sudah')
                ->exists();

            if ($isProcessed) {
                return response()->json(['status' => 'error', 'message' => 'Gagal! Data sudah diproses.'], 403);
            }

            if ($kd_jenis_prw) {
                DB::table('permintaan_pemeriksaan_radiologi')->where(['noorder' => $noorder, 'kd_jenis_prw' => $kd_jenis_prw])->delete();
            } else {
                DB::table('permintaan_pemeriksaan_radiologi')->where('noorder', $noorder)->delete();
                DB::table('permintaan_radiologi')->where('noorder', $noorder)->delete();
            }

            if (DB::table('permintaan_pemeriksaan_radiologi')->where('noorder', $noorder)->count() == 0) {
                DB::table('permintaan_radiologi')->where('noorder', $noorder)->delete();
            }

            return response()->json(['status' => 'success-hapus-rad', 'message' => 'Data berhasil dihapus']);
        });
    }
}