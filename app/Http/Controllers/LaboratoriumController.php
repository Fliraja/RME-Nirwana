<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ralan\StorePermintaanLabRequest;
use App\Models\PermintaanLab;
use App\Services\Ralan\LaboratoriumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboratoriumController extends Controller
{
    public function __construct(private LaboratoriumService $service) {}

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
        return response()->json(
            $this->service->cariPemeriksaan($request->search ?? '', $request->no_rawat)
        );
    }

    public function storePermintaanLab(StorePermintaanLabRequest $request)
    {
        return response()->json($this->service->simpanPermintaan($request->all()));
    }

    public function getTemplates($kd_jenis_prw)
    {
        return response()->json($this->service->templates($kd_jenis_prw));
    }

    public function destroyLab(Request $request)
    {
        $res = $this->service->hapus($request->noorder, $request->kd_jenis_prw, $request->id_template);
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
    }
}
