<?php

namespace App\Http\Controllers;

use App\Services\Ralan\KfrService;
use Barryvdh\DomPDF\Facade\Pdf;

/** PDF formulir Rehab Medik (KFR) kunjungan ralan poli P003. Siapa pun yang login boleh mencetak, seperti resume. */
class KfrRalanController extends Controller
{
    public function __construct(private KfrService $service) {}

    public function asesmenPdf(string $no_rawat)
    {
        return $this->pdf($no_rawat, 'ralan.pdf.kfr_asesmen_pdf', 'Asesmen_KFR_');
    }

    public function ujiFungsiPdf(string $no_rawat)
    {
        return $this->pdf($no_rawat, 'ralan.pdf.kfr_uji_fungsi_pdf', 'Uji_Fungsi_KFR_');
    }

    private function pdf(string $no_rawat, string $view, string $awalanBerkas)
    {
        $noRawat = str_replace('-', '/', $no_rawat);
        $data = $this->service->dataPdf($noRawat);
        abort_if(!$data, 404, 'Formulir KFR belum disimpan untuk kunjungan ini.');

        return Pdf::loadView($view, $data)->setPaper('a4', 'portrait')
            ->stream($awalanBerkas . str_replace('/', '_', $noRawat) . '.pdf');
    }
}
