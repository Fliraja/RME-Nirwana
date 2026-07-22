<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiagnosaPasien;
use App\Models\ProsedurPasien;
use Illuminate\Support\Facades\DB;

class DiagnosaProsedurController extends Controller
{
    public function index($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        $pasien = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->where('no_rawat', $no_rawat)
            ->first();

        $diagnosa = DiagnosaPasien::with('penyakit')
            ->where('no_rawat', $no_rawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        $prosedur = ProsedurPasien::with('icd9')
            ->where('no_rawat', $no_rawat)
            ->orderBy('prioritas', 'asc')
            ->get();

        return view('ralan.diagnosa-prosedur', compact('pasien', 'diagnosa', 'prosedur', 'no_rawat'));
    }

    public function searchIcd10(Request $request)
    {
        $search = $request->search;

        $items = DB::table('penyakit')
            ->where(function ($q) use ($search) {
                $q->where('kd_penyakit', 'like', "%$search%")
                  ->orWhere('nm_penyakit', 'like', "%$search%");
            })
            ->limit(20)
            ->get();

        $response = [];
        foreach ($items as $item) {
            $response[] = [
                'id'   => $item->kd_penyakit,
                'text' => $item->kd_penyakit . ' - ' . $item->nm_penyakit
            ];
        }

        return response()->json($response);
    }

    public function searchIcd9(Request $request)
    {
        $search = $request->search;

        $items = DB::table('icd9')
            ->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%$search%")
                  ->orWhere('deskripsi_panjang', 'like', "%$search%")
                  ->orWhere('deskripsi_pendek', 'like', "%$search%");
            })
            ->limit(20)
            ->get();

        $response = [];
        foreach ($items as $item) {
            $text = $item->kode . ' - ' . ($item->deskripsi_panjang ?: $item->deskripsi_pendek);
            $response[] = [
                'id'   => $item->kode,
                'text' => $text
            ];
        }

        return response()->json($response);
    }

    public function storeDiagnosa(Request $request)
    {
        $request->validate([
            'no_rawat'    => 'required',
            'kd_penyakit' => 'required|array|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $regPeriksa = DB::table('reg_periksa')->where('no_rawat', $request->no_rawat)->first();
            $statusLanjut = ($regPeriksa && strtolower($regPeriksa->status_lanjut) === 'ranap') ? 'Ranap' : 'Ralan';

            foreach ($request->kd_penyakit as $index => $kd) {
                $hasPrimary = DB::table('diagnosa_pasien')
                    ->where('no_rawat', $request->no_rawat)
                    ->where('prioritas', '1')
                    ->exists();

                $prioritas = ($request->prioritas && $index === 0) ? $request->prioritas : ($hasPrimary ? '2' : '1');
                $statusPenyakit = $request->status_penyakit ?? 'Lama';

                DB::table('diagnosa_pasien')->updateOrInsert(
                    [
                        'no_rawat'    => $request->no_rawat,
                        'kd_penyakit' => $kd,
                        'status'      => $statusLanjut,
                    ],
                    [
                        'prioritas'       => $prioritas,
                        'status_penyakit' => $statusPenyakit,
                    ]
                );
            }

            return response()->json([
                'status'  => 'success-diagnosa',
                'message' => 'Diagnosa ICD-10 berhasil disimpan'
            ]);
        });
    }

    public function storeProsedur(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
            'kode'     => 'required|array|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $regPeriksa = DB::table('reg_periksa')->where('no_rawat', $request->no_rawat)->first();
            $statusLanjut = ($regPeriksa && strtolower($regPeriksa->status_lanjut) === 'ranap') ? 'Ranap' : 'Ralan';

            foreach ($request->kode as $index => $kd) {
                $hasPrimary = DB::table('prosedur_pasien')
                    ->where('no_rawat', $request->no_rawat)
                    ->where('prioritas', '1')
                    ->exists();

                $prioritas = $hasPrimary ? '2' : '1';

                DB::table('prosedur_pasien')->updateOrInsert(
                    [
                        'no_rawat' => $request->no_rawat,
                        'kode'     => $kd,
                        'status'   => $statusLanjut,
                    ],
                    [
                        'prioritas' => $prioritas,
                        'jumlah'    => $request->jumlah ?? 1,
                    ]
                );
            }

            return response()->json([
                'status'  => 'success-prosedur',
                'message' => 'Prosedur ICD-9 berhasil disimpan'
            ]);
        });
    }

    public function destroyDiagnosa($no_rawat, $kd_penyakit)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        DB::table('diagnosa_pasien')
            ->where('no_rawat', $no_rawat)
            ->where('kd_penyakit', $kd_penyakit)
            ->delete();

        return response()->json([
            'status'  => 'success-hapus-diagnosa',
            'message' => 'Diagnosa berhasil dihapus'
        ]);
    }

    public function destroyProsedur($no_rawat, $kode)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        DB::table('prosedur_pasien')
            ->where('no_rawat', $no_rawat)
            ->where('kode', $kode)
            ->delete();

        return response()->json([
            'status'  => 'success-hapus-prosedur',
            'message' => 'Prosedur berhasil dihapus'
        ]);
    }
}
