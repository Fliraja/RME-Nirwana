<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\MasterAturanPakai;
use App\Models\PermintaanLab;
use App\Models\PermintaanRadiologi;
use App\Models\RegPeriksa;
use App\Models\ResepObat;
use App\Services\Ranap\RanapDashboardService;
use App\Services\Ranap\RanapPenunjangService;
use App\Services\Ranap\RanapSoapService;
use App\Services\Ranap\RanapVitalSignService;
use App\Services\Ralan\DiagnosaProsedurService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RanapController extends Controller
{
    public function __construct(
        private RanapDashboardService $dashboardService,
        private RanapSoapService $soapService,
        private RanapVitalSignService $vitalService,
        private RanapPenunjangService $penunjangService,
        private DiagnosaProsedurService $diagnosaService
    ) {}

    /**
     * Dashboard & Daftar Pasien Rawat Inap
     */
    public function index(Request $request)
    {
        $isAdmin = session('role') === 'admin';
        $kd_dokter = $isAdmin ? null : (Auth::user()->decrypted_id ?? null);

        $action = $request->query('action');
        $no_rawat = $request->query('no_rawat');

        if ($action === 'view' && $no_rawat) {
            return $this->detail($request, $no_rawat);
        }

        $tanggalKeluar = $request->input('tanggal_keluar') ?? date('Y-m-d');
        $search = $request->input('search');

        $stats = $this->dashboardService->getStatistik($kd_dokter);
        $pasienAktif = $this->dashboardService->getPasienAktif($kd_dokter, $search);
        $pasienPulang = $this->dashboardService->getPasienPulang($tanggalKeluar, $kd_dokter, $search);

        $data = [
            'stats'         => $stats,
            'pasienAktif'   => $pasienAktif,
            'pasienPulang'  => $pasienPulang,
            'tanggalKeluar' => $tanggalKeluar,
            'search'        => $search,
            'nama_dokter'   => $isAdmin ? session('nama_lengkap') : (Auth::user()->dokter_data->nm_dokter ?? 'Dokter'),
            'role'          => session('role'),
            'isAdmin'       => $isAdmin,
        ];

        return view('ranap.index', $data);
    }

    /**
     * Halaman Workspace Detail Pasien Rawat Inap
     */
    public function detail(Request $request, string $no_rawat)
    {
        $isAdmin = session('role') === 'admin';

        $detailPasien = RegPeriksa::with([
                'pasien',
                'penjab',
                'kamarInap' => function ($q) {
                    $q->orderBy('tgl_masuk', 'desc')->orderBy('jam_masuk', 'desc');
                },
                'kamarInap.kamar.bangsal',
                'dpjpRanap.dokter',
            ])
            ->where('no_rawat', $no_rawat)
            ->first();

        if (!$detailPasien) {
            return redirect()->route('ranap.index')->with('error', 'Data pasien rawat inap tidak ditemukan.');
        }

        $kamarAktif = $detailPasien->kamarInap->first();

        // 5 Riwayat kunjungan terakhir untuk tab riwayat awal
        $riwayat = RegPeriksa::with([
                'poliklinik', 'dokter', 'pemeriksaanRalan', 'pemeriksaanRanap',
                'resepObat.resepDokter.dataBarang', 'detailObat.barang',
                'detailLab.template', 'gambarRadiologi'
            ])
            ->where('no_rkm_medis', $detailPasien->no_rkm_medis)
            ->where('stts', '!=', 'Batal')
            ->orderBy('tgl_registrasi', 'DESC')
            ->paginate(5, ['*'], 'riwayat_page');

        $riwayat->appends($request->all());

        $data = [
            'detailPasien' => $detailPasien,
            'kamarAktif'   => $kamarAktif,
            'riwayat'      => $riwayat,
            'nama_dokter'  => $isAdmin ? session('nama_lengkap') : (Auth::user()->dokter_data->nm_dokter ?? 'Dokter'),
            'role'         => session('role'),
            'isAdmin'      => $isAdmin,
        ];

        return view('ranap.detail', $data);
    }

    // =========================================================================
    // MODULAR TABS AJAX LOADERS & HANDLERS
    // =========================================================================

    /**
     * Tab Riwayat Kunjungan
     */
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
            ->paginate(5, ['*'], 'riwayat_page');

        $detailPasien = RegPeriksa::where('no_rkm_medis', $no_rkm_medis)->first();

        return view('ranap.tabs.riwayat', compact('riwayat', 'detailPasien'));
    }

    /**
     * Tab SOAP (Form + Timeline Histori)
     */
    public function getSoapPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $pasien = RegPeriksa::where('no_rawat', $no_rawat)->first();
        $riwayatSoap = $this->soapService->getRiwayatSoap($no_rawat);
        $currentUserNip = Auth::user()->decrypted_id ?? '';

        return view('ranap.tabs.soap', compact('pasien', 'riwayatSoap', 'currentUserNip'));
    }

    public function storeSoap(Request $request)
    {
        $request->validate([
            'no_rawat'  => 'required',
            'keluhan'   => 'nullable|string',
            'penilaian' => 'nullable|string',
        ]);

        $nip = Auth::user()->decrypted_id ?? 'DOKTER';
        $res = $this->soapService->simpan($request->all(), $nip);

        return response()->json($res);
    }

    public function destroySoap(Request $request)
    {
        $nip = Auth::user()->decrypted_id ?? '';
        $isAdmin = session('role') === 'admin';
        $res = $this->soapService->hapus($request->no_rawat, $request->tgl, $request->jam, $nip, $isAdmin);

        return response()->json($res);
    }

    /**
     * Tab TTV & SBAR (Form + Tabel Observasi)
     */
    public function getVitalPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $pasien = RegPeriksa::where('no_rawat', $no_rawat)->first();
        $riwayatTtv = $this->vitalService->getRiwayatTtv($no_rawat);
        $currentUserNip = Auth::user()->decrypted_id ?? '';

        return view('ranap.tabs.ttv', compact('pasien', 'riwayatTtv', 'currentUserNip'));
    }

    public function storeVital(Request $request)
    {
        $request->validate([
            'no_rawat' => 'required',
        ]);

        $nip = Auth::user()->decrypted_id ?? 'DOKTER';
        $res = $this->vitalService->simpan($request->all(), $nip);

        return response()->json($res);
    }

    public function destroyVital(Request $request)
    {
        $nip = Auth::user()->decrypted_id ?? '';
        $isAdmin = session('role') === 'admin';
        $res = $this->vitalService->hapus($request->no_rawat, $request->tgl, $request->jam, $nip, $isAdmin);

        return response()->json($res);
    }

    /**
     * Tab Diagnosa & Prosedur (ICD-10 & ICD-9)
     */
    public function getDiagnosaPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $data = $this->diagnosaService->dataPasien($no_rawat);

        return view('ranap.tabs.diagnosa', array_merge($data, ['no_rawat' => $no_rawat]));
    }

    /**
     * Tab Resep Obat (Form Order + Antrean Hari Ini + Riwayat Pemberian Obat Bangsal)
     */
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

        // Riwayat obat yang telah diberikan/disuntikkan di bangsal
        $riwayatPemberian = $this->penunjangService->getRiwayatPemberianObat($no_rawat);

        return view('ranap.tabs.resep', compact('resep', 'pasien', 'masterAturan', 'masterMetode', 'riwayatPemberian'));
    }

    /**
     * Tab Laboratorium (Form Permintaan + Riwayat Order + Hasil Lab Terverifikasi)
     */
    public function getLabPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        $pasien = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->where('no_rawat', $no_rawat)
            ->first();

        $riwayatOrder = PermintaanLab::with(['pemeriksaan.jenisPerawatan'])
            ->where('no_rawat', $no_rawat)
            ->where('tgl_permintaan', date('Y-m-d'))
            ->get();

        // Hasil tes laboratorium resmi
        $hasilLab = $this->penunjangService->getHasilLab($no_rawat);

        return view('ranap.tabs.lab', compact('pasien', 'riwayatOrder', 'hasilLab'));
    }

    /**
     * Tab Radiologi (Form Permintaan + Riwayat Order + Galeri Gambar & Ekspertise)
     */
    public function getRadiologiPasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);

        $pasien = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->where('no_rawat', $no_rawat)
            ->first();

        $riwayatOrder = PermintaanRadiologi::with(['pemeriksaan.jenisPerawatan'])
            ->where('no_rawat', $no_rawat)
            ->where('tgl_permintaan', date('Y-m-d'))
            ->get();

        // Gambar dan Hasil Ekspertise
        $hasilRadiologi = $this->penunjangService->getHasilRadiologi($no_rawat);

        return view('ranap.tabs.radiologi', compact('pasien', 'riwayatOrder', 'hasilRadiologi'));
    }
}
