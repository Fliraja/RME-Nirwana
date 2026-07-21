<?php

namespace App\Http\Controllers;

use App\Models\ResepObat;
use App\Models\DataBarang;
use App\Models\RegPeriksa;
use App\Models\ResepDokter;
use Illuminate\Http\Request;
use App\Models\MasterAturanPakai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResepController extends Controller
{
    public function getResepPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        $resep = ResepObat::with([
            'resepDokter.dataBarang',
            'resepRacikan.detailRacikan.dataBarang',
            'resepRacikan.metodeRacik'
            ])
            ->where('no_rawat', $no_rawat)
            ->where('tgl_peresepan', date('Y-m-d'))
            ->first();

        $masterAturan = MasterAturanPakai::all();
        $masterMetode = DB::table('metode_racik')->get();
        $pasien = RegPeriksa::where('no_rawat', $no_rawat)->first();

        return view('ralan.resep', compact('resep', 'pasien', 'masterAturan', 'masterMetode'));
    }

    public function storeResepObat(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
            'kode_obat' => 'required',
            'jumlah' => 'required|numeric|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $tgl_sekarang = date('Y-m-d');
            $jam_sekarang = date('H:i:s');

            $resep = ResepObat::where('no_rawat', $request->no_rawat)
                ->where('tgl_peresepan', $tgl_sekarang)
                ->first();

            if (!$resep) {
                $lastNo = ResepObat::where('tgl_peresepan', $tgl_sekarang)
                    ->max(DB::raw('CONVERT(RIGHT(no_resep, 10), signed)')) ?? 0;
                
                $nextNoResep = date('Ymd') . sprintf('%04s', ($lastNo + 1)); 

                $kd_dokter = Auth::user()->decrypted_id;

                $resep = ResepObat::create([
                    'no_resep'      => $nextNoResep,
                    'tgl_perawatan' => $tgl_sekarang,
                    'jam' => $jam_sekarang,
                    'no_rawat'      => $request->no_rawat,
                    'kd_dokter'     => $kd_dokter, 
                    'tgl_peresepan' => $tgl_sekarang,
                    'jam_peresepan' => $jam_sekarang,
                    'status'        => 'ralan',
                    'tgl_penyerahan'        => null,
                    'jam_penyerahan'        => null,
                ]);
            }

            ResepDokter::updateOrCreate(
                [
                    'no_resep'  => $resep->no_resep,
                    'kode_brng' => $request->kode_obat
                ],
                [
                    'jml'           => $request->jumlah,
                    'aturan_pakai'  => $request->aturan_pakai == 'lainnya' ? $request->aturan_pakai_lainnya : $request->aturan_pakai
                ]
            );

            return response()->json([
                'status' => 'success-obat',
                'message' => 'Obat berhasil ditambahkan ke resep'
            ]);
        });
    }

    public function storeResepRacikan(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
            'nama_racik' => 'required',
            'kd_racik' => 'required',
            'jml_dr' => 'required|numeric|min:1',
            'aturan_racik' => 'required',
            'detail_obat'   => 'required|array|min:1',
            'detail_obat.*.kode_brng' => 'required',
            'detail_obat.*.p1'        => 'required|numeric', 
            'detail_obat.*.p2'        => 'required|numeric', 
            'detail_obat.*.kandungan' => 'required',        
            'detail_obat.*.jml'       => 'required|numeric',
        ]);

        return DB::transaction(function () use ($request) {
            $tgl_sekarang = date('Y-m-d');
            $jam_sekarang = date('H:i:s');

            $resep = ResepObat::where('no_rawat', $request->no_rawat)
                ->where('tgl_peresepan', $tgl_sekarang)
                ->first();

            if (!$resep) {
                $lastNo = ResepObat::where('tgl_peresepan', $tgl_sekarang)
                    ->max(DB::raw('CONVERT(RIGHT(no_resep, 10), signed)')) ?? 0;
                
                $nextNoResep = date('Ymd') . sprintf('%04s', ($lastNo + 1)); 

                $kd_dokter = Auth::user()->decrypted_id;

                $resep = ResepObat::create([
                    'no_resep'      => $nextNoResep,
                    'tgl_perawatan' => $tgl_sekarang,
                    'jam' => $jam_sekarang,
                    'no_rawat'      => $request->no_rawat,
                    'kd_dokter'     => $kd_dokter, 
                    'tgl_peresepan' => $tgl_sekarang,
                    'jam_peresepan' => $jam_sekarang,
                    'status'        => 'ralan',
                    'tgl_penyerahan'        => null,
                    'jam_penyerahan'        => null,
                ]);
            }

            $no_racik = DB::table('resep_dokter_racikan')
                ->where('no_resep', $resep->no_resep)
                ->max('no_racik') ?? 0;
            $new_no_racik = $no_racik + 1;

            DB::table('resep_dokter_racikan')->insert([
                'no_resep'     => $resep->no_resep,
                'no_racik'     => $no_racik + 1,
                'nama_racik'   => $request->nama_racik,
                'kd_racik'     => $request->kd_racik,
                'jml_dr'       => $request->jml_dr,
                'aturan_pakai' => $request->aturan_racik,
                'keterangan'   => $request->keterangan ?? '-',
            ]);

            foreach ($request->detail_obat as $obat) {
                DB::table('resep_dokter_racikan_detail')->insert([
                    'no_resep'  => $resep->no_resep,
                    'no_racik'  => $new_no_racik,
                    'kode_brng' => $obat['kode_brng'],
                    'p1'        => $obat['p1'],
                    'p2'        => $obat['p2'],
                    'kandungan' => $obat['kandungan'],
                    'jml'       => $obat['jml'] 
                ]);
            }

            return response()->json([
                'status' => 'success-racik',
                'message' => 'Resep racikan berhasil ditambahkan',
                'no_resep' => $resep->no_resep,
                'no_racik' => $no_racik + 1
            ]);
        });
    }

    public function deleteResepObat($no_resep, $kode_brng)
    {
        return DB::transaction(function () use ($no_resep, $kode_brng) {
            try {
                DB::table('resep_dokter')
                    ->where('no_resep', $no_resep)
                    ->where('kode_brng', $kode_brng)
                    ->delete();

                $countObat = DB::table('resep_dokter')->where('no_resep', $no_resep)->count();
                $countRacik = DB::table('resep_dokter_racikan')->where('no_resep', $no_resep)->count();

                if ($countObat == 0 && $countRacik == 0) {
                    DB::table('resep_obat')->where('no_resep', $no_resep)->delete();
                }

                return response()->json([
                    'status' => 'success-hapus-obat',
                    'message' => 'Item resep berhasil dihapus'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error-obat',
                    'message' => 'Gagal menghapus item: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    public function deleteResepRacikan($no_resep, $no_racik)
    {
        return DB::transaction(function () use ($no_resep, $no_racik) {
            try {
                DB::table('resep_dokter_racikan_detail')
                    ->where('no_resep', $no_resep)
                    ->where('no_racik', $no_racik)
                    ->delete();

                DB::table('resep_dokter_racikan')
                    ->where('no_resep', $no_resep)
                    ->where('no_racik', $no_racik)
                    ->delete();

                $countObat = DB::table('resep_dokter')->where('no_resep', $no_resep)->count();
                $countRacik = DB::table('resep_dokter_racikan')->where('no_resep', $no_resep)->count();

                if ($countObat == 0 && $countRacik == 0) {
                    DB::table('resep_obat')->where('no_resep', $no_resep)->delete();
                }

                return response()->json([
                    'status' => 'success-hapus-racikan',
                    'message' => 'Satu kelompok racikan berhasil dihapus'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error-racikan',
                    'message' => 'Gagal menghapus racikan: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    public function getObat(Request $request)
    {
        $search = $request->search;

        $obat = DataBarang::select('kode_brng', 'nama_brng')
            ->where(function($query) use ($search) {
                $query->where('nama_brng', 'LIKE', "%$search%")
                    ->orWhere('kode_brng', 'LIKE', "%$search%");
            })
            ->where('status', '1') 
            ->limit(20)            
            ->get();

        $response = [];
        foreach($obat as $item){
            $response[] = [
                'id'    => $item->kode_brng,
                'text'  => $item->nama_brng
            ];
        }

        return response()->json($response);
    }
}
