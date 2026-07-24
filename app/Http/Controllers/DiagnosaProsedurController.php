<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ralan\StoreDiagnosaRequest;
use App\Http\Requests\Ralan\StoreProsedurRequest;
use App\Services\Ralan\DiagnosaProsedurService;
use Illuminate\Http\Request;

class DiagnosaProsedurController extends Controller
{
    public function __construct(private DiagnosaProsedurService $service) {}

    public function index($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $data = $this->service->dataPasien($no_rawat);

        return view('ralan.diagnosa-prosedur', array_merge($data, ['no_rawat' => $no_rawat]));
    }

    public function searchIcd10(Request $request)
    {
        return response()->json($this->service->searchIcd10($request->search ?? ''));
    }

    public function searchIcd9(Request $request)
    {
        return response()->json($this->service->searchIcd9($request->search ?? ''));
    }

    public function storeDiagnosa(StoreDiagnosaRequest $request)
    {
        return response()->json($this->service->simpanDiagnosa(
            $request->no_rawat,
            $request->kd_penyakit,
            $request->prioritas,
            $request->status_penyakit
        ));
    }

    public function storeProsedur(StoreProsedurRequest $request)
    {
        return response()->json($this->service->simpanProsedur(
            $request->no_rawat,
            $request->kode,
            $request->jumlah
        ));
    }

    public function destroyDiagnosa($no_rawat, $kd_penyakit)
    {
        return response()->json($this->service->hapusDiagnosa(str_replace('-', '/', $no_rawat), $kd_penyakit));
    }

    public function destroyProsedur($no_rawat, $kode)
    {
        return response()->json($this->service->hapusProsedur(str_replace('-', '/', $no_rawat), $kode));
    }
}
