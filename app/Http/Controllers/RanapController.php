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
use App\Services\Ranap\RanapResumeService;
use App\Services\Ralan\LaboratoriumService;
use App\Services\Ralan\RadiologiService;
use App\Http\Requests\Ranap\StoreSoapRanapRequest;
use App\Http\Requests\Ranap\StoreVitalRanapRequest;
use App\Http\Requests\Ranap\StoreResumeRanapRequest;
use App\Http\Requests\Ralan\StoreDiagnosaRequest;
use App\Http\Requests\Ralan\StoreProsedurRequest;
use App\Http\Requests\Ralan\StorePermintaanLabRequest;
use App\Http\Requests\Ralan\StorePermintaanRadiologiRequest;
use App\Support\Alergi;
use Barryvdh\DomPDF\Facade\Pdf;
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
        private DiagnosaProsedurService $diagnosaService,
        private RanapResumeService $resumeService,
        private LaboratoriumService $labService,
        private RadiologiService $radiologiService,
        private ResepController $resepController
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
            'alergiPasien' => Alergi::terakhirRanap($no_rawat),
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

    public function storeSoap(StoreSoapRanapRequest $request)
    {
        $nip = Auth::user()->decrypted_id ?? 'DOKTER';
        $res = $this->soapService->simpan($request->validated(), $nip, session('role') === 'admin');
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
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

    public function storeVital(StoreVitalRanapRequest $request)
    {
        $nip = Auth::user()->decrypted_id ?? 'DOKTER';
        $res = $this->vitalService->simpan($request->validated(), $nip, session('role') === 'admin');
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
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

        $resep = $this->resepHariIni($no_rawat);

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

        $riwayatOrder = $this->orderHariIni(PermintaanLab::class, $no_rawat);

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

        $riwayatOrder = $this->orderHariIni(PermintaanRadiologi::class, $no_rawat);

        // Gambar dan Hasil Ekspertise
        $hasilRadiologi = $this->penunjangService->getHasilRadiologi($no_rawat);

        return view('ranap.tabs.radiologi', compact('pasien', 'riwayatOrder', 'hasilRadiologi'));
    }

    /**
     * Tab Resume Pasien (Ringkasan Pulang Medis)
     */
    public function getResumePasien($no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $data = $this->resumeService->getResumeData($no_rawat);
        $data['currentUserNip'] = Auth::user()->decrypted_id ?? '';
        $data['isAdmin'] = session('role') === 'admin';

        return view('ranap.tabs.resume', $data);
    }

    public function storeResumePasien(StoreResumeRanapRequest $request)
    {
        $defaultKdDokter = Auth::user()->decrypted_id ?? null;
        $res = $this->resumeService->simpan($request->validated(), $defaultKdDokter);

        return response()->json($res);
    }

    /**
     * Cetak Lembar Resmi Resume Medis Pasien Rawat Inap (PDF)
     */
    public function pdfResume(string $no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $data = $this->resumeService->getPdfData($no_rawat);

        if (!$data) {
            return back()->with('error', 'Resume medis untuk pasien ini belum dibuat / disimpan.');
        }

        $pdf = Pdf::loadView('ranap.pdf.resume_pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Resume_Medis_' . str_replace('/', '_', $no_rawat) . '.pdf');
    }

    // =========================================================================
    // DECOUPLED RANAP ACTION HANDLERS
    // =========================================================================

    public function storeDiagnosa(StoreDiagnosaRequest $request)
    {
        return response()->json($this->diagnosaService->simpanDiagnosa(
            $request->no_rawat,
            $request->kd_penyakit
        ));
    }

    public function storeProsedur(StoreProsedurRequest $request)
    {
        return response()->json($this->diagnosaService->simpanProsedur(
            $request->no_rawat,
            $request->kode,
            $request->jumlah ?? []
        ));
    }

    public function destroyDiagnosa(string $no_rawat, string $kd_penyakit)
    {
        return response()->json($this->diagnosaService->hapusDiagnosa(str_replace('-', '/', $no_rawat), $kd_penyakit));
    }

    public function destroyProsedur(string $no_rawat, string $kode)
    {
        return response()->json($this->diagnosaService->hapusProsedur(str_replace('-', '/', $no_rawat), $kode));
    }

    public function storeResepObat(Request $request)
    {
        return $this->resepController->storeResepObat($request);
    }

    public function storeResepRacikan(Request $request)
    {
        return $this->resepController->storeResepRacikan($request);
    }

    public function destroyResepObat(string $no_resep, string $kode_brng)
    {
        return $this->resepController->deleteResepObat($no_resep, $kode_brng);
    }

    public function destroyResepRacikan(string $no_resep, string $no_racik)
    {
        return $this->resepController->deleteResepRacikan($no_resep, $no_racik);
    }

    public function storePermintaanLab(StorePermintaanLabRequest $request)
    {
        return response()->json($this->labService->simpanPermintaan($request->all()));
    }

    public function destroyLab(Request $request)
    {
        $res = $this->labService->hapus($request->noorder, $request->kd_jenis_prw, $request->id_template);
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
    }

    public function storePermintaanRadiologi(StorePermintaanRadiologiRequest $request)
    {
        $res = $this->radiologiService->simpanPermintaan($request->all());
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
    }

    public function destroyRadiologi(Request $request)
    {
        $res = $this->radiologiService->hapus($request->noorder, $request->kd_jenis_prw);
        $code = $res['code'] ?? 200;
        unset($res['code']);

        return response()->json($res, $code);
    }

    /**
     * Fragmen tabel riwayat saja, untuk reload setelah simpan/hapus tanpa
     * memuat ulang form tab (Tom Select & isian form tetap).
     */
    public function getTable(string $tabel, string $no_rawat)
    {
        $no_rawat = str_replace('-', '/', $no_rawat);
        $currentUserNip = Auth::user()->decrypted_id ?? '';

        return match ($tabel) {
            'soap'      => view('ranap.tabs.partials.soap_table', [
                'riwayatSoap' => $this->soapService->getRiwayatSoap($no_rawat), 'currentUserNip' => $currentUserNip,
            ]),
            'ttv'       => view('ranap.tabs.partials.ttv_table', [
                'riwayatTtv' => $this->vitalService->getRiwayatTtv($no_rawat), 'currentUserNip' => $currentUserNip,
            ]),
            'diagnosa'  => view('ranap.tabs.partials.diagnosa_table', $this->diagnosaService->dataPasien($no_rawat)),
            'prosedur'  => view('ranap.tabs.partials.prosedur_table', $this->diagnosaService->dataPasien($no_rawat)),
            'resep'     => view('ranap.tabs.partials.resep_table', ['resep' => $this->resepHariIni($no_rawat)]),
            'lab'       => view('ranap.tabs.partials.lab_table', ['riwayatOrder' => $this->orderHariIni(PermintaanLab::class, $no_rawat)]),
            'radiologi' => view('ranap.tabs.partials.radiologi_table', ['riwayatOrder' => $this->orderHariIni(PermintaanRadiologi::class, $no_rawat)]),
        };
    }

    private function resepHariIni(string $no_rawat): ?ResepObat
    {
        return ResepObat::with([
                'resepDokter.dataBarang',
                'resepRacikan.detailRacikan.dataBarang',
                'resepRacikan.metodeRacik'
            ])
            ->where('no_rawat', $no_rawat)
            ->where('tgl_peresepan', date('Y-m-d'))
            ->first();
    }

    /** @param class-string<PermintaanLab|PermintaanRadiologi> $model */
    private function orderHariIni(string $model, string $no_rawat)
    {
        return $model::with(['pemeriksaan.jenisPerawatan'])
            ->where('no_rawat', $no_rawat)
            ->where('tgl_permintaan', date('Y-m-d'))
            ->get();
    }
}
