<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RalanController;
use App\Http\Controllers\ResepController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RadiologiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaboratoriumController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['multi.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    //Ralan Routes
    Route::match(['GET', 'POST'], '/ralan', [RalanController::class, 'index'])->name('ralan.index');

    //Riwayat
    Route::get('/ralan/riwayat/{no_rkm_medis}', [RalanController::class, 'getRiwayatPasien'])->name('ralan.riwayat');

    //Pemeriksaan
    Route::get('/ralan/soap/{no_rawat}', [RalanController::class, 'getSoapPasien'])->name('ralan.get-soap');
    Route::post('/ralan/soap/simpan', [RalanController::class, 'storeSOAP'])->name('ralan.soap.simpan');
    Route::get('/ralan/get-vital-pasien/{no_rawat}', [RalanController::class, 'getVitalPasien'])->name('ralan.get-vital');
    Route::post('/ralan/store-vital', [RalanController::class, 'storeVital'])->name('ralan.store-vital');

    //Resep Obat
    Route::get('/ralan/get-resep-pasien/{no_rawat}', [ResepController::class, 'getResepPasien'])->name('ralan.get-resep');
    Route::post('/ralan/store-resep-obat', [ResepController::class, 'storeResepObat'])->name('ralan.store-resep-obat');
    Route::delete('/ralan/delete-resep-obat/{no_resep}/{kode_brng}', [ResepController::class, 'deleteResepObat'])->name('ralan.delete-resep-obat');
    Route::get('/ralan/search-obat', [ResepController::class, 'getObat'])->name('ralan.search-obat');
    Route::post('/ralan/store-resep-racikan', [ResepController::class, 'storeResepRacikan'])->name('ralan.store-resep-racikan');
    Route::delete('/ralan/delete-resep-racikan/{no_resep}/{no_racik}', [ResepController::class, 'deleteResepRacikan'])->name('ralan.delete-resep-racikan');

    //Laboratorium
    Route::get('/ralan/get-lab-pasien/{no_rawat}', [LaboratoriumController::class, 'getLabPasien'])->name('ralan.get-lab-pasien');
    Route::get('/ralan/search-pemeriksaan-lab', [LaboratoriumController::class, 'getPemeriksaan'])->name('ralan.search-lab');
    Route::get('/ralan/get-templates-lab/{kd_jenis_prw}', [LaboratoriumController::class, 'getTemplates'])->name('ralan.get-templates-lab');
    Route::post('/ralan/store-permintaan-lab', [LaboratoriumController::class, 'storePermintaanLab'])->name('ralan.store-lab');
    Route::delete('/ralan/delete-lab/{noorder}/{kd_jenis_prw?}/{id_template?}', [LaboratoriumController::class, 'destroyLab'])->name('ralan.delete-lab');

    //Radiologi
    Route::get('/ralan/get-radiologi-pasien/{no_rawat}', [RadiologiController::class, 'getRadiologiPasien'])->name('ralan.get-radiologi-pasien');
    Route::get('/ralan/search-pemeriksaan-radiologi', [RadiologiController::class, 'getPemeriksaanRadiologi'])->name('ralan.search-radiologi');
    Route::post('/ralan/store-permintaan-radiologi', [RadiologiController::class, 'storePermintaanRadiologi'])->name('ralan.store-radiologi');
    Route::delete('/ralan/delete-radiologi/{noorder}/{kd_jenis_prw?}', [RadiologiController::class, 'destroyRadiologi'])->name('ralan.delete-radiologi');

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
