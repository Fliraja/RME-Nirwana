<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\RegPeriksa;
use Illuminate\Http\Request;
use App\Models\PemeriksaanRalan;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Ralan\StoreSoapRequest;
use App\Http\Requests\Ralan\StoreVitalSignRequest;
use App\Services\Ralan\SoapService;
use App\Services\Ralan\VitalSignService;

class RalanController extends Controller
{

    public function index(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $kd_dokter = $isAdmin ? null : (Auth::user()->decrypted_id ?? null);
        
        $action = $request->query('action');
        $no_rawat = $request->query('no_rawat');
        $tanggal = $request->input('tanggal') ?? date('Y-m-d');

        $filter_dokter = $request->input('kd_dokter');

        $data = [
            'action' => $action,
            'nama_dokter' => $isAdmin ? session('nama_lengkap') : (Auth::user()->dokter_data->nm_dokter ?? 'Dokter'),
            'tanggal' => $tanggal,
            'selected_dokter' => $filter_dokter,
            'role' => session('role')
        ];

        if ($isAdmin) {
            $data['listDokter'] = Dokter::where('status', '1') 
                                    ->orderBy('nm_dokter', 'ASC')
                                    ->get();
        }

        if ($action == 'view' && $no_rawat) {
            $data['detailPasien'] = RegPeriksa::with(['pasien', 'penjab'])
                ->where('no_rawat', $no_rawat)
                ->first();

            if ($data['detailPasien']) {
                $data['riwayat'] = RegPeriksa::with([
                        'poliklinik', 'dokter', 'pemeriksaanRalan', 'pemeriksaanRanap',
                        'resepObat.resepDokter.dataBarang', 'detailObat.barang',
                        'detailLab.template', 'gambarRadiologi'
                    ])
                    ->where('no_rkm_medis', $data['detailPasien']->no_rkm_medis)
                    ->where('stts', '!=', 'Batal')
                    ->orderBy('tgl_registrasi', 'DESC')
                    ->paginate(5, ['*'], 'riwayat_page');

                $data['riwayat']->appends($request->all());
            }
        } 
        else {
            $query = RegPeriksa::with(['pasien', 'poliklinik', 'penjab'])
                ->where('tgl_registrasi', $tanggal)
                ->where('stts', '!=', 'Batal');

            if ($isAdmin) {
                if ($filter_dokter) {
                    $query->where('kd_dokter', $filter_dokter);
                }
            } else {
                $kd_dokter_auth = Auth::user()->decrypted_id;
                $query->where('kd_dokter', $kd_dokter_auth);
            }

            $data['daftarPasien'] = $query->orderBy('no_reg', 'ASC')->get();
        }

        return view('ralan.index', $data);
    }

    public function getRiwayatPasien($no_rkm_medis)
    {
        $riwayat = RegPeriksa::with([
                'poliklinik',
                'dokter',
                'pemeriksaanRalan', 
                'pemeriksaanRanap',
                'detailObat.barang', 
                'detailLab.template', 
                'gambarRadiologi'
            ])
            ->where('no_rkm_medis', $no_rkm_medis)
            ->where('stts', '!=', 'Batal')
            ->orderBy('tgl_registrasi', 'DESC') 
            ->get();

        return view('ralan.riwayat', compact('riwayat'));
    }

    public function getSoapPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat); 

        $pasien = RegPeriksa::with(['pemeriksaanRalan'])
            ->where('no_rawat', $no_rawat)
            ->first();

        if ($pasien) {
            return view('ralan.soap', compact('pasien'));
        }

        return "<div class='alert alert-danger'>Data pendaftaran tidak ditemukan.</div>";
    }

    public function storeSOAP(StoreSoapRequest $request, SoapService $service)
    {
        try {
            $service->simpan($request->all());

            return response()->json(['status' => 'success-soap', 'message' => 'Data SOAP berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan SOAP: ' . $e->getMessage()], 500);
        }
    }

    public function getVitalPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat); 

        $pasien = RegPeriksa::with(['pemeriksaanRalan'])
            ->where('no_rawat', $no_rawat)
            ->first();

        if ($pasien) {
            return view('ralan.vital-sign', compact('pasien'));
        }

        return "<div class='alert alert-danger'>Data vital sign tidak ditemukan.</div>";
    }

    public function storeVital(StoreVitalSignRequest $request, VitalSignService $service)
    {
        try {
            $service->simpan($request->all());

            return response()->json(['status' => 'success-vital', 'message' => 'Data Vital Sign berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan Vital Sign: ' . $e->getMessage()], 500);
        }
    }
}