<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderIgdTerkunci;
use App\Services\Igd\IgdOrderService;
use App\Services\Igd\IgdPasienService;
use App\Services\Ralan\LaboratoriumService;
use App\Services\Ralan\RadiologiService;
use Illuminate\Http\Request;

/** Tab Permintaan di halaman pasien IGD: lab, radiologi (dan resep di tahap berikutnya). */
class IgdOrderController extends Controller
{
    public function __construct(
        private IgdOrderService $order,
        private IgdPasienService $pasien,
        private LaboratoriumService $lab,
        private RadiologiService $radiologi,
    ) {}

    public function fragmen(string $no_rawat)
    {
        $noRawat = IgdController::noRawat($no_rawat);

        try {
            $this->order->pastikanPasienIgd($noRawat);
        } catch (OrderIgdTerkunci $e) {
            abort($e->status, $e->getMessage());
        }

        $ranap = $this->pasien->masukRanap($noRawat);
        $lab = $this->order->daftarLab($noRawat);
        $radiologi = $this->order->daftarRadiologi($noRawat);

        return view('igd.tabs.permintaan', [
            'noRawat'        => $noRawat,
            'lab'            => $lab,
            'radiologi'      => $radiologi,
            'jumlah'         => $lab->count() + $radiologi->count(),
            'pesanTerkunci'  => $ranap ? $this->order->pesanTerkunci($ranap->format('H.i')) : null,
            'usulanDiagnosa' => $this->order->usulanDiagnosaKlinis($noRawat),
        ]);
    }

    public function simpanLab(Request $request)
    {
        $data = $request->validate($this->aturanPermintaan() + [
            'detail_lab'   => 'nullable|array',
            'detail_lab.*' => 'array',
        ]);

        return $this->jalankan($data['no_rawat'], fn () => $this->lab->simpanPermintaan($this->bersihkan($data)));
    }

    public function hapusLab(Request $request, string $noorder)
    {
        $noRawat = (string) $request->validate(['no_rawat' => 'required|string'])['no_rawat'];

        return $this->jalankan($noRawat, function () use ($noorder, $noRawat) {
            $this->order->pastikanMilik('permintaan_lab', $noorder, $noRawat);

            return $this->lab->hapus($noorder, null, null);
        });
    }

    public function simpanRadiologi(Request $request)
    {
        $data = $request->validate($this->aturanPermintaan());

        return $this->jalankan($data['no_rawat'], fn () => $this->radiologi->simpanPermintaan($this->bersihkan($data)));
    }

    public function hapusRadiologi(Request $request, string $noorder)
    {
        $noRawat = (string) $request->validate(['no_rawat' => 'required|string'])['no_rawat'];

        return $this->jalankan($noRawat, function () use ($noorder, $noRawat) {
            $this->order->pastikanMilik('permintaan_radiologi', $noorder, $noRawat);

            return $this->radiologi->hapus($noorder, null);
        });
    }

    private function aturanPermintaan(): array
    {
        return [
            'no_rawat'           => 'required|string',
            'kd_jenis_prw'       => 'required|array|min:1',
            'kd_jenis_prw.*'     => 'string',
            'diagnosa_klinis'    => 'nullable|string|max:80',
            'informasi_tambahan' => 'nullable|string|max:60',
        ];
    }

    private function bersihkan(array $data): array
    {
        $data['diagnosa_klinis'] = trim((string) ($data['diagnosa_klinis'] ?? '')) ?: '-';
        $data['informasi_tambahan'] = trim((string) ($data['informasi_tambahan'] ?? '')) ?: '-';

        return $data;
    }

    /** Penjaga IGD lalu aksi service; penolakan service (sudah diproses) dikirim sebagai 409. */
    private function jalankan(string $noRawat, callable $aksi)
    {
        try {
            $this->order->pastikanBolehOrder($noRawat);
            $hasil = $aksi();
        } catch (OrderIgdTerkunci $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->status);
        }

        $kode = $hasil['code'] ?? 200;
        unset($hasil['code']);

        return response()->json($hasil, $kode >= 400 ? 409 : 200);
    }
}
