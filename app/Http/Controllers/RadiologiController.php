<?php

namespace App\Http\Controllers;

use App\Models\PermintaanRadiologi;
use App\Services\Ralan\RadiologiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologiController extends Controller
{
    public function __construct(private RadiologiService $service) {}

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
        return response()->json(
            $this->service->cariPemeriksaan($request->search ?? '', $request->no_rawat)
        );
    }

    public function storePermintaanRadiologi(Request $request)
    {
        $request->validate([
            'no_rawat'         => 'required',
            'kd_jenis_prw_rad' => 'required|array|min:1',
        ]);

        return response()->json($this->service->simpanPermintaan($request->all()));
    }

    public function destroyRadiologi($noorder, $kd_jenis_prw = null)
    {
        $res = $this->service->hapus($noorder, $kd_jenis_prw);
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
    }
}
