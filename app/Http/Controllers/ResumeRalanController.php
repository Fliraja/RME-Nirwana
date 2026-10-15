<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ralan\VerifikasiResumeRalanRequest;
use App\Services\Ralan\ResumeRalanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResumeRalanController extends Controller
{
    public function __construct(private ResumeRalanService $service) {}

    public function show(Request $request, string $no_rawat)
    {
        $noRawat = str_replace('-', '/', $no_rawat);

        return view('ralan.resume', $this->service->dataTab(
            $noRawat,
            Auth::user()->decrypted_id ?? null,
            $request->query('pakai') === 'terbaru'
        ));
    }

    public function verifikasi(VerifikasiResumeRalanRequest $request)
    {
        $this->service->verifikasi($request->no_rawat, $request->validated(), (string) (Auth::user()->decrypted_id ?? ''));

        return response()->json(['status' => 'success', 'message' => 'Resume diverifikasi dan disimpan.']);
    }

    public function pdf(string $no_rawat)
    {
        $noRawat = str_replace('-', '/', $no_rawat);
        $data = $this->service->dataPdf($noRawat);
        abort_if(!$data, 404, 'Resume belum tersedia. Isi Penilaian (A) di SOAP terlebih dahulu.');

        return Pdf::loadView('ralan.pdf.resume_pdf', $data)->setPaper('a4', 'portrait')
            ->stream('Resume_Ralan_' . str_replace('/', '_', $noRawat) . '.pdf');
    }
}
