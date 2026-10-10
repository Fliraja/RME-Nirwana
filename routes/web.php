<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RalanController;
use App\Http\Controllers\ResepController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RadiologiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaboratoriumController;
use App\Http\Controllers\DiagnosaProsedurController;

use App\Http\Controllers\RanapController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['multi.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    //Ranap Routes
    Route::match(['GET', 'POST'], '/ranap', [RanapController::class, 'index'])->name('ranap.index');
    Route::get('/ranap/riwayat/{no_rkm_medis}', [RanapController::class, 'getRiwayatPasien'])->name('ranap.riwayat');
    Route::get('/ranap/soap/{no_rawat}', [RanapController::class, 'getSoapPasien'])->name('ranap.get-soap');
    Route::post('/ranap/soap/simpan', [RanapController::class, 'storeSoap'])->name('ranap.soap.simpan');
    Route::post('/ranap/soap/hapus', [RanapController::class, 'destroySoap'])->name('ranap.soap.hapus');
    Route::get('/ranap/get-vital-pasien/{no_rawat}', [RanapController::class, 'getVitalPasien'])->name('ranap.get-vital');
    Route::post('/ranap/store-vital', [RanapController::class, 'storeVital'])->name('ranap.store-vital');
    Route::post('/ranap/vital/hapus', [RanapController::class, 'destroyVital'])->name('ranap.vital.hapus');
    Route::get('/ranap/get-diagnosa-prosedur/{no_rawat}', [RanapController::class, 'getDiagnosaPasien'])->name('ranap.get-diagnosa-prosedur');
    Route::get('/ranap/get-resep-pasien/{no_rawat}', [RanapController::class, 'getResepPasien'])->name('ranap.get-resep');
    Route::get('/ranap/get-lab-pasien/{no_rawat}', [RanapController::class, 'getLabPasien'])->name('ranap.get-lab');
    Route::get('/ranap/get-radiologi-pasien/{no_rawat}', [RanapController::class, 'getRadiologiPasien'])->name('ranap.get-radiologi');
    Route::get('/ranap/get-resume-pasien/{no_rawat}', [RanapController::class, 'getResumePasien'])->name('ranap.get-resume');
    Route::post('/ranap/store-resume-pasien', [RanapController::class, 'storeResumePasien'])->name('ranap.store-resume');

    //Ralan Routes
    Route::match(['GET', 'POST'], '/ralan', [RalanController::class, 'index'])->name('ralan.index');
    Route::post('/ralan/tandai-perawat', [RalanController::class, 'tandaiPerawat'])->name('ralan.tandai-perawat');

    //Riwayat
    Route::get('/ralan/riwayat/{no_rkm_medis}', [RalanController::class, 'getRiwayatPasien'])->name('ralan.riwayat');

    //Pemeriksaan
    Route::get('/ralan/soap/{no_rawat}', [RalanController::class, 'getSoapPasien'])->name('ralan.get-soap');
    Route::post('/ralan/soap/simpan', [RalanController::class, 'storeSOAP'])->name('ralan.soap.simpan');
    Route::get('/ralan/get-vital-pasien/{no_rawat}', [RalanController::class, 'getVitalPasien'])->name('ralan.get-vital');
    Route::post('/ralan/store-vital', [RalanController::class, 'storeVital'])->name('ralan.store-vital');

    //Resep Obat
    Route::get('/ralan/get-resep-pasien/{no_rawat}', [ResepController::class, 'getResepPasien'])->name('ralan.get-resep');
    Route::get('/ralan/get-resep-table/{no_rawat}', [ResepController::class, 'getResepTable'])->name('ralan.get-resep-table');
    Route::post('/ralan/store-resep-obat', [ResepController::class, 'storeResepObat'])->name('ralan.store-resep-obat');
    Route::delete('/ralan/delete-resep-obat/{no_resep}/{kode_brng}', [ResepController::class, 'deleteResepObat'])->name('ralan.delete-resep-obat');
    Route::get('/ralan/search-obat', [ResepController::class, 'getObat'])->name('ralan.search-obat');
    Route::post('/ralan/store-resep-racikan', [ResepController::class, 'storeResepRacikan'])->name('ralan.store-resep-racikan');
    Route::delete('/ralan/delete-resep-racikan/{no_resep}/{no_racik}', [ResepController::class, 'deleteResepRacikan'])->name('ralan.delete-resep-racikan');

    //Laboratorium
    Route::get('/ralan/get-lab-pasien/{no_rawat}', [LaboratoriumController::class, 'getLabPasien'])->name('ralan.get-lab-pasien');
    Route::get('/ralan/get-lab-table/{no_rawat}', [LaboratoriumController::class, 'getLabTable'])->name('ralan.get-lab-table');
    Route::get('/ralan/search-pemeriksaan-lab', [LaboratoriumController::class, 'getPemeriksaan'])->name('ralan.search-lab');
    Route::get('/ralan/get-templates-lab/{kd_jenis_prw}', [LaboratoriumController::class, 'getTemplates'])->name('ralan.get-templates-lab');
    Route::post('/ralan/store-permintaan-lab', [LaboratoriumController::class, 'storePermintaanLab'])->name('ralan.store-lab');
    Route::delete('/ralan/delete-lab/{noorder}/{kd_jenis_prw?}/{id_template?}', [LaboratoriumController::class, 'destroyLab'])->name('ralan.delete-lab');

    //Radiologi
    Route::get('/ralan/get-radiologi-pasien/{no_rawat}', [RadiologiController::class, 'getRadiologiPasien'])->name('ralan.get-radiologi-pasien');
    Route::get('/ralan/get-radiologi-table/{no_rawat}', [RadiologiController::class, 'getRadiologiTable'])->name('ralan.get-radiologi-table');
    Route::get('/ralan/search-pemeriksaan-radiologi', [RadiologiController::class, 'getPemeriksaanRadiologi'])->name('ralan.search-radiologi');
    Route::post('/ralan/store-permintaan-radiologi', [RadiologiController::class, 'storePermintaanRadiologi'])->name('ralan.store-radiologi');
    Route::delete('/ralan/delete-radiologi/{noorder}/{kd_jenis_prw?}', [RadiologiController::class, 'destroyRadiologi'])->name('ralan.delete-radiologi');

    //Diagnosa & Prosedur
    Route::get('/ralan/get-diagnosa-prosedur/{no_rawat}', [DiagnosaProsedurController::class, 'index'])->name('ralan.get-diagnosa-prosedur');
    Route::get('/ralan/search-icd10', [DiagnosaProsedurController::class, 'searchIcd10'])->name('ralan.search-icd10');
    Route::get('/ralan/search-icd9', [DiagnosaProsedurController::class, 'searchIcd9'])->name('ralan.search-icd9');
    Route::post('/ralan/store-diagnosa', [DiagnosaProsedurController::class, 'storeDiagnosa'])->name('ralan.store-diagnosa');
    Route::post('/ralan/store-prosedur', [DiagnosaProsedurController::class, 'storeProsedur'])->name('ralan.store-prosedur');
    Route::delete('/ralan/delete-diagnosa/{no_rawat}/{kd_penyakit}', [DiagnosaProsedurController::class, 'destroyDiagnosa'])->name('ralan.delete-diagnosa');
    Route::delete('/ralan/delete-prosedur/{no_rawat}/{kode}', [DiagnosaProsedurController::class, 'destroyProsedur'])->name('ralan.delete-prosedur');

    //Report Routes
    Route::get('/report/soap', [ReportController::class, 'indexSoap'])->name('report.soap-index');
    Route::get('/report/soap-pdf', [ReportController::class, 'pdfSoap'])->name('report.soap-pdf');
    Route::get('/report/vitalsign', [ReportController::class, 'indexVitalSign'])->name('report.vitalsign-index');
    Route::get('/report/vitalsign-pdf', [ReportController::class, 'pdfVitalSign'])->name('report.vitalsign-pdf');
    Route::get('/report/lab', [ReportController::class, 'indexLab'])->name('report.lab-index');
    Route::get('/report/lab-pdf', [ReportController::class, 'pdfLab'])->name('report.lab-pdf');
    Route::get('/report/radiologi', [ReportController::class, 'indexRadiologi'])->name('report.radiologi-index');
    Route::get('/report/radiologi-pdf', [ReportController::class, 'pdfRadiologi'])->name('report.radiologi-pdf');
    Route::get('/report/resep', [ReportController::class, 'indexResep'])->name('report.resep-index');
    Route::get('/report/resep-pdf', [ReportController::class, 'pdfResep'])->name('report.resep-pdf');
    Route::get('/report/riwayat-pdf/{no_rkm_medis}', [ReportController::class, 'pdfRiwayat'])->name('report.riwayat-pdf');
});
